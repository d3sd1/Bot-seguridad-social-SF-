<?php
namespace App\Command;
set_time_limit(0);

use App\Utils\Commands;
use App\Utils\CommandUtils;
use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class StartBotCommand extends ContainerAwareCommand
{

    private $processQueue = true;
    private $em;

    protected function configure()
    {
        $this
            ->setName("start-bot")
            ->setDescription('Starts the bot.')
            ->setHelp('This command allows you to start the bot server, and the required automatizing scripts. You have to get Firefox installed and the bot running under nginx.');
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        try {
            /*
             * Iniciar objetos.
             */
            $this->em = $this->getContainer()->get('doctrine')->getManager();
            $bot = new BotServer($this->em, $this->getContainer());
            $sockets = new SocketServer($this->em, $this->getContainer());

            /*
             * Iniciar bot.
             */
            $bot->startBot();

            /*
             * Iniciar escuchadores.
             */
            $sockets->startSockets();

            /*
             * Preparar query para la cola.
             */
            $qb = $this->em->createQueryBuilder();
            $taskQuery = $qb->select(array('q'))
                ->from('App:Queue', 'q')
                ->orderBy('q.id', 'ASC')
                ->setMaxResults(1)
                ->getQuery();

            /*
             * Comunicar que se finalizó la carga.
             */

            /*
             * Procesar cola.
             */
            $commandManager = new Commands();
            while ($this->processQueue) {
                if ($bot->getServerStatus()->getStatus() === "SS_PAGE_DOWN") {
                    $this->getContainer()->get("app.dblogger")->success("Página de la seguridad social inactiva. Esperando " . getenv("SS_PAGE_DOWN_SLEEP") . " segundos.");
                    sleep(getenv("SS_PAGE_DOWN_SLEEP"));
                }
                /*
                 * Recuperar los resultados actuales.
                 */
                $task = $taskQuery->getOneOrNullResult();

                /*
                 * Comprobar que la tarea no está caducada y que
                 * hay algo por hacer.
                 */
                $taskTimeOut = false;

                if($task != null && $task->getDateInit() != null) {
                    $taskTimeOut = $task->getDateInit()->diff(new \DateTime())->s > GET_ENV('OPERATION_TIMEOUT_SECONDS');
                }

                if ($task != null && $taskTimeOut) {
                        //TODO: eliminar de la cola

                    $task->setStatus($this->em->getRepository("App:ProcessStatus")->findOneBy(['status' => 'TIMED_OUT'])->getId());
                    $bot->setServerStatus("WAITING_TASKS");
                    $commandManager->killProcessByName("firefox");
                    $this->processQueue = $sockets->waitForTask();
                }
                /*
                 * Si hay cosas por hacer, se hacen.
                 * Si no, esperar a que haya algo por sockets.
                 */
                else if($task != null) {
                    $bot->setServerStatus("RUNNING");
                    $bot->processTask($task);
                }
                else {
                    $bot->setServerStatus("WAITING_TASKS");
                    $commandManager->killProcessByName("firefox");
                    $this->processQueue = $sockets->waitForTask();
                }
            }
            $sockets->closeSockets();

        } catch (\Exception $e) {
            $this->getContainer()->get("app.dblogger")->error("Ha ocurrido un error interno en el bot [COMANDO]: " . $e->getMessage());
        }
    }
}