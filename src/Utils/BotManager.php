<?php

namespace App\Utils;

use App\Entity\BotSession;
use App\Entity\ServerStatus;
use Doctrine\ORM\EntityManager;
use Symfony\Component\DependencyInjection\ContainerInterface;
use phpseclib\Net\SSH2;

/*
 * Clase para simplificar la capa SO - BOT
 */

class BotManager
{
    private $container;
    private $em;

    public function __construct(ContainerInterface $container, EntityManager $em)
    {
        $this->container = $container;
        $this->em = $em;
    }

    public function restartServerSO() {
        $this->setBotStatus("OFFLINE");
        $this->abortPendingOperations();
        $this->container->get("so.commands")->restartServerSO();
    }

    public function start($abortPendingOperations = false) {
        $this->setBotStatus("BOOTING");
        $botSession = new BotSession();
        $botSession->setDatetime();
        $this->em->persist($botSession);
        $this->em->flush();
        $success = true;
        if($abortPendingOperations) {
            $success = $this->abortPendingOperations();
        }

        if($success) {
            $this->setBotStatus("WAITING_TASKS");
        }
        else {
            $this->setBotStatus("OFFLINE");
        }
        sleep(3);

        return $success;
    }

    private function abortPendingOperations() {
        try {
            /* Abortar todas las peticiones previas */
            $qb = $this->em->createQueryBuilder();
            $getQueue = $qb->select(array('q'))
                ->from('App:Queue', 'q')
                ->orderBy('q.id', 'ASC')
                ->getQuery()->getResult();

            $abortedStatus = $this->em->getRepository("App:ProcessStatus")->findOneBy(['status' => "ABORTED"]);

            foreach ($getQueue as $queueProccess) {
                /* Marcar como abortada */
                $qb = $this->em->createQueryBuilder();

                $mainName = explode("_", strtolower($queueProccess->getProcessType()->getType()));
                $finalClassName = "";
                foreach ($mainName as $subName) {
                    $finalClassName .= ucfirst($subName);
                }

                $operation = $qb->select(array('t'))
                    ->from('App:Queue', 'q')
                    ->join("App:" . $finalClassName, "t", "WITH", "q.referenceId = t.id")
                    ->getQuery()
                    ->setMaxResults(1)
                    ->getOneOrNullResult();
                $operation->setStatus($abortedStatus->getId());
                /* Eliminar de la cola */
                $this->container->get("app.dblogger")->success("Abortada petición con ID " . $queueProccess->getId() . " del tipo " . $queueProccess->getProcessType()->getType());
                $this->em->remove($queueProccess);
            }
            $this->em->flush();
        }
        catch(\Exception $e)
        {
            $this->container->get("app.dblogger")->success("Excepción al abortar peticiones pendientes: " . $e->getMessage());
            return false;
        }

        return true;
    }

    public function close() {

        /*
         * Marcar servidor como inactivo
         */
        $success = $this->container->get("so.commands")->resetNavigator() && $this->container->get("so.commands")->killBot();
        if($success) {
            $this->setBotStatus("OFFLINE");
        }
        else {
            $this->setBotStatus("CRASHED");
        }
        return $success;
    }

    public function setBotStatus($status) {
        $serverStatusRows = $this->em->getRepository("App:ServerStatus")->findAll();
        if(count($serverStatusRows) >= 2) {
            $this->em->createQueryBuilder()
                ->delete('App:ServerStatus', 's')
                ->where('s.id != :serverId')
                ->setParameter('serverId', 1)
                ->getQuery()->execute();
        }

        if(count($serverStatusRows) <= 0){
            $bootServer = new ServerStatus();
            $bootServer->setId(1);
            $bootServer->setCurrentStatus($this->getStatus('OFFLINE'));
            $bootServer->setSessionAlerts(0);
            $bootServer->setSessionErrors(0);
            $bootServer->setSessionProcessedRequests(0);
            $bootServer->setSessionWarnings(0);
            $this->em->persist($bootServer);
            $this->em->flush();
        }
        $serverStatusRows = $this->em->getRepository("App:ServerStatus")->findAll();
        $serverRealStatus = $this->getBotStatus();
        $serverRealStatus->setCurrentStatus($this->getStatus($status));
        $this->em->flush();
    }

    public function getBotStatus() {
        $srvStatus = $this->em->getRepository("App:ServerStatus")->findAll();
        if(count($srvStatus) == 0)
        {
            $this->setBotStatus("OFFLINE");
            $srvStatus = $this->em->getRepository("App:ServerStatus")->findAll();
        }

        $serverStatus = $srvStatus[0];
        /*
        * Check if bot queue has data and it's not being proccessed.
        */
        if(count($this->em->getRepository("App:Queue")->findAll()) > 0) {
            $actualAction = $this->em->getRepository("App:Queue")->findOneBy(array('id' => 'ASC'));
            if(null != $actualAction && null != $actualAction->getDateAdded() && $actualAction->getDateAdded()->modify("+5 minutes") < (new DateTime())) {
                $serverStatus = $this->em->getRepository("App:ServerStatusOptions")->findOneBy(array('status' => 'CRASHED'));
            }
        }

        return $serverStatus;
    }
    public function getStatus($status) {
        return $this->em->getRepository("App:ServerStatusOptions")->findOneBy(['status' => $status]);
    }

    public function getSession(): BotSession
    {
        $em = $this->container->get("doctrine.orm.entity_manager");
        return $em->createQueryBuilder()
            ->select('s')
            ->from('App:BotSession', 's')
            ->setMaxResults(1)
            ->orderBy('s.id', 'DESC')
            ->getQuery()->getSingleResult();
    }

    /* Esta función previene al bot de colgarse si algo va mal */
    public function preventHanging() {
        if($this->container->get("so.commands")->isBotHanging()) {
            $this->container->get("app.dblogger")->success("Bot is hanging on. We are restarting it...");
            $this->close();
            // Aqui tirar el mismo comando que en el cron: checker para ver estado e iniciar si es necesario
            $this->container->get("so.commands")->runCronChecker();
        }
    }

}