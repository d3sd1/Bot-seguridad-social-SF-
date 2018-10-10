<?php

namespace App\Command;
set_time_limit(0);

use App\Utils\Commands;
use App\Utils\CommandUtils;
use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class StartBot extends ContainerAwareCommand
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

    private $socket = false;

    /*
     * Iniciar escuchadores y prevenir que se deniegue la ejecución.
     */
    public function socketCreate()
    {
        if ($this->socket === false) {
            try {
                $this->socket = socket_create(AF_INET, SOCK_STREAM, 0);
                socket_set_option($this->socket, SOL_SOCKET, SO_REUSEADDR, 1);
                socket_set_option($this->socket, SOL_SOCKET, SO_RCVTIMEO, array('sec' => 0, 'usec' => 0));
                socket_set_option($this->socket, SOL_SOCKET, SO_SNDTIMEO, array('sec' => 0, 'usec' => 0));
                socket_bind($this->socket, getenv("INTERNAL_SOCKETS_HOST"), getenv("INTERNAL_SOCKETS_PORT"));
                socket_listen($this->socket, 3);
            } catch (\Exception $e) {
                $this->getContainer()->get("app.dblogger")->error("El servidor ya estaba iniciado.");
                exit();
            }
        }
        return $this->socket;
    }

    /*
     * Cerrar escuchadores.
     */
    public function socketKill()
    {
        socket_close($this->socket);
    }

    /*
     * Esperar a operación por sockets.
     */
    public function waitForTask()
    {
        try {
            $spawn = socket_accept($this->socket);
            $input = socket_read($spawn, 1024);
            $data = trim($input);
            socket_close($spawn);
        } catch (\Exception $e) {
            return false;
        }
        return true;
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        try {
            /*
             * Iniciar objetos.
             */
            $this->em = $this->getContainer()->get('doctrine')->getManager();
            $bot = new BotServer($this->em, $this->getContainer());

            /*
             * Iniciar bot.
             */
            $bot->startBot();

            /*
             * Iniciar escuchadores.
             */
            $this->socketCreate();

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
                 * Si hay cosas por hacer, se hacen.
                 * Si no, esperar a que haya algo por sockets.
                 */
                if ($task != null) {
                    $bot->setServerStatus("RUNNING");
                    $bot->processTask($task);
                } else {
                    $bot->setServerStatus("WAITING_TASKS");
                    $commandManager->killProcessByName("firefox");
                    $this->processQueue = $this->waitForTask();
                }
            }
            $this->socketKill();

        } catch (\Exception $e) {
            $this->getContainer()->get("app.dblogger")->error("Ha ocurrido un error interno en el bot [COMANDO]: " . $e->getMessage());
        }
    }
}