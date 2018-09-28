<?php

namespace App\Command;
set_time_limit(0);

use App\Entity\Queue;
use App\Entity\ServerStatus;
use Doctrine\ORM\EntityManager;
use Symfony\Component\DependencyInjection\ContainerInterface;

class BotServer
{
    private $server = false;
    private $em;
    private $container;

    public function __construct(EntityManager $em, ContainerInterface $container)
    {
        $this->em = $em;
        $this->container = $container;
    }

    /*
     * Manda el estado del servidor (String)
     */
    public function setServerStatus($serverStatus)
    {
        $this->server->setCurrentStatus($this->em->getRepository("App:ServerStatusOptions")->findOneBy(['status' => $serverStatus]));
        $this->em->flush();
    }

    /*
     * Devuelve el estado del servidor.
     */
    public function getServerStatus()
    {
        return $this->server->getCurrentStatus();
    }

    /*
     * Iniciar bot.
     */
    public function startBot()
    {
        if ($this->server === false) {
            $this->server = $this->em->getRepository("App:ServerStatus")->findOneBy(['id' => 1]);
        }
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

            new $taskClass($taskData, $this->container, $this->em, $this->server);

        } catch (\Exception $e) {
            $this->container->get("app.dblogger")->error("Ha ocurrido un error interno en el bot [BOT TASK MANAGER]: " . $e->getMessage());
        }
    }

}