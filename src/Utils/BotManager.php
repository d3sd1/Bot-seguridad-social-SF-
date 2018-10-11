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

    public function start() {
        $this->close();
        $this->abortPendingOperations();

        $botSession = new BotSession();
        $botSession->setDatetime();
        $this->em->persist($botSession);
        $this->em->flush();
    }

    private function abortPendingOperations() {
        /* Abortar todas las peticiones previas */
        $qb = $this->em->createQueryBuilder();
        $getQueue = $qb->select(array('q'))
            ->from('App:Queue', 'q')
            ->orderBy('q.id', 'ASC')
            ->getQuery()->getResult();

        $abortedStatus = $this->em->getRepository("App:ProcessStatus")->findOneBy(['status' => "ABORTED"]);

        foreach($getQueue as $queueProccess) {
            /* Marcar como abortada */
            $qb = $this->em->createQueryBuilder();

            $mainName = explode("_",strtolower($queueProccess->getProcessType()->getType()));
            $finalClassName = "";
            foreach($mainName as $subName) {
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

    public function close() {

        /*
         * Marcar servidor como inactivo
         */
        $em = $this->em;
        $qb = $em->createQueryBuilder();
        $server = $qb->select(array('s'))
            ->from('App:ServerStatus', 's')
            ->orderBy('s.id', 'ASC')
            ->getQuery()
            ->setMaxResults(1)
            ->getOneOrNullResult();
        if ($server !== null) {
            $server->setCurrentStatus($em->getRepository("App:ServerStatusOptions")->findOneBy(['status' => "OFFLINE"]));
            $em->flush();
        }
        $this->container->get("app.dblogger")->success("Servidor detenido.");
    }

    public function status() {

        $em = $this->get("doctrine.orm.entity_manager");
        $ssh = $this->get("app.ssh");
        if (!$ssh->connect()) {
            return $this->container->get("response")->error(500, "SERVER_NOT_CONFIGURED");
        }
        $botStatus = $ssh->getStatus();
        $ssh->disconnect();

        /*
         * Revisar que a nivel de sistema operativo este OK.
         */
        if ($botStatus == "") {
            $cliServerStatus = false;
        } else {
            $cliServerStatus = true;
        }
        $qb = $em->createQueryBuilder();
        $serverStatus = $qb->select(array('s'))
            ->from('App:ServerStatus', 's')
            ->orderBy('a.id', 'DESC')
            ->setMaxResults(1)
            ->orderBy("s.id", "ASC")
            ->getQuery()
            ->getOneOrNullResult();
        if ($serverStatus !== null) {
            $dbServerStatus = $serverStatus->getCurrentStatus()->getStatus();
        } else {
            $dbServerStatus = "UNKNOWN";
        }
        /*
         * Si en la base de datos está marcado como activo
         * pero no está corriendo, ha crasheado.
         * Si está corriendo en cli o no se tiene estado, se coge el estado de la DB.
         * Y si no, es que está offline (si no está cargando).
         */
        if (!$cliServerStatus && $dbServerStatus !== "OFFLINE" && $dbServerStatus !== "BOOTING") {
            $serverStatusName = "CRASHED";
        } else if (!$cliServerStatus && $dbServerStatus !== "BOOTING") {
            $serverStatusName = "OFFLINE";
        } else {
            $serverStatusName = $dbServerStatus;
        }
        if($serverStatusName != "WAITING_TASKS") {
            //No notificar consultas de estado del servidor.
            //$this->get("app.dblogger")->success("Estado del servidor consultado: " . $serverStatusName);
        }
    }

    public function setBotStatus($status) {
        $serverStatusRows = $this->em->getRepository("App:ServerStatus")->findAll();
        if($serverStatusRows >= 2) {
            $this->em->createQueryBuilder()
                ->delete('App:ServerStatus', 's')
                ->where('s.id != :serverId')
                ->setParameter('serverId', 1)
                ->getQuery()->execute();
        }

        if($serverStatusRows <= 0){
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
        $serverRealStatus = $this->getBotStatus();
        $serverRealStatus->setCurrentStatus($this->getStatus($status));
        $this->em->flush();
    }

    public function getBotStatus() {
        return $this->em->getRepository("App:ServerStatus")->findAll()[0];
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

    public function setHeadlessEnv() {
        /*
        * Chrome tiene otro manager para esto, esto es sólo para morzilla
        */
        if($GLOBALS["debug"]) {
            $this->runSyncCommand("EXPORT MOZ_HEADLESS=0");
        }
        else {
            $this->runSyncCommand("EXPORT MOZ_HEADLESS=1");
        }
    }
}