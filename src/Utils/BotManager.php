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
    private $ssh;

    public function __construct(ContainerInterface $container, EntityManager $em)
    {
        $this->container = $container;
        $this->em = $em;
        $this->ssh = $this->get("app.ssh");
    }

    public function start() {
        /*
         * Iniciar sesión del bot.
         */

        $ssh = $this->get("app.ssh");
        if (!$ssh->connect()) {
            return $this->container->get("response")->error(500, "SERVER_NOT_CONFIGURED");
        }

        /*
         * Abrir interfaz gráfica.
         */
        $ssh->startX();

        /*
         * Iniciar el bot y selenium.
         */
        $ssh->startSelenium();
        $ssh->startBot();
        /*
         * Matar todos procesos el bot que estuvieran corriendo antes.
         */
        $ssh->killBotProcess();
        $this->get("app.dblogger")->success("Servidor iniciado.");
        $ssh->disconnect();
        $botSession = new BotSession();
        $botSession->setDatetime();
        $this->em->persist($botSession);
        $this->em->flush();
    }

    public function close() {
        $ssh = $this->get("app.ssh");
        if (!$ssh->connect()) {
            return $this->container->get("response")->error(500, "SERVER_NOT_CONFIGURED");
        }
        /*
         * Matar todos procesos el bot que estén corriendo.
         */
        $ssh->killBotProcess();
        $ssh->disconnect();

        /*
         * Marcar servidor como inactivo
         */
        $em = $this->get("doctrine.orm.entity_manager");
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
        $this->get("app.dblogger")->success("Servidor detenido.");
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
}