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
    private $socket = false;
    private $em;
    private $bm;
    private $log;

    protected function configure()
    {
        $this
            ->setName("start-bot")
            ->setDescription('Starts the bot.')
            ->setHelp('This command allows you to start the bot server, and the required automatizing scripts. You have to get Firefox installed and the bot running under nginx.');
    }

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
                $this->log->error("El servidor ya estaba iniciado.");
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

    /*
     * Procesa una tarea.
     */
    public function processTask(Queue $task)
    {

        try {
            /*
             * Cargar nombre de la clase del controlador
             * de Selenium.
             */
            $task = explode("_", $task->getProcessType()->getType());
            $taskPlainClass = "";
            foreach ($task as $part) {
                $taskPlainClass .= ucfirst(strtolower($part));
            }
            $taskClass = "\App\Selenium\\" . $taskPlainClass;

            /*
             * Cargar la operación requerida.
             */

            $qb = $this->em->createQueryBuilder();
            $taskData = $qb->select(array('t'))
                ->from('App:Queue', 'q')
                ->join("App:" . $taskPlainClass, "t", "WITH", "q.referenceId = t.id")
                ->getQuery()
                ->setMaxResults(1)
                ->getOneOrNullResult();


            /*
             * Instanciar la automatización
             */

            new $taskClass($taskData, $this->getContainer(), $this->em, $this->server);

        } catch (\Exception $e) {
            $this->log->error("Ha ocurrido un error interno en el bot [BOT TASK MANAGER]: " . $e->getMessage());
        }
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        try {

            /*
             * Iniciar objetos.
             */
            $this->em = $this->getContainer()->get('doctrine')->getManager();
            $this->bm = $this->getContainer()->get('bot.manager');

            /*
             * Marcar estado del bot como iniciando.
             */
            $this->bm->setBotStatus("BOOTING");

            /*
             * Iniciar escuchadores BOT - REST.
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
             * Marcar estado del bot como iniciando.
             */
            $this->bm->setBotStatus("WAITING_TASKS");

            /*
             * Procesar cola.
             */
            $commandManager = new Commands();
            while ($this->processQueue) {
                if ($this->bm->getBotStatus() === "SS_PAGE_DOWN") {
                    $this->log->error("Página de la seguridad social inactiva. Esperando " . getenv("SS_PAGE_DOWN_SLEEP") . " segundos.");
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
                    $this->bm->setBotStatus("RUNNING");
                    $this->processTask($task);
                } else {
                    $this->bm->setBotStatus("WAITING_TASKS");
                    $commandManager->killProcessByName("firefox");
                    $this->processQueue = $this->waitForTask();
                }
            }
            $this->socketKill();

        } catch (\Exception $e) {
            $this->log->error("Ha ocurrido un error interno en el bot [COMANDO]: " . $e->getMessage());
        }
    }
}