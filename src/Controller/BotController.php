<?php

namespace App\Controller;

use App\Entity\BotSession;
use App\Utils\BotSsh;
use FOS\RestBundle\Controller\Annotations as FOSRest;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;
use App\Entity\ServerStatus;
use Doctrine\ORM\Query;

/**
 * Bot remote controller.
 *
 * @Route("/bot")
 */
class BotController extends Controller
{

    /**
     * Ver logs del bot.
     * @FOSRest\Get("/logs/internal")
     */
    public function logsAction(Request $request)
    {
        $query = $this->getDoctrine()
            ->getRepository('App:InternalLog')
            ->createQueryBuilder('l')
            ->orderBy("l.datetime", "DESC")
            ->getQuery();
        $result = $query->getResult(Query::HYDRATE_ARRAY);

        if (count($result) > 0) {
            return $this->container->get("response")->success("LOADED", $result);
        } else {
            return $this->container->get("response")->success("NO_LOGS");
        }
    }

    /**
     * Ver cola del bot.
     * @FOSRest\Get("/queue")
     */
    public function queueAction(Request $request)
    {
        $query = $this->getDoctrine()
            ->getRepository('App:Queue')
            ->createQueryBuilder('q')
            ->orderBy("q.id", "ASC")
            ->getQuery();
        $result = $query->getResult();
        if (count($result) > 0) {
            return $this->container->get("response")->success("LOADED", $result);
        } else {
            return $this->container->get("response")->success("NO_QUEUE");
        }
    }

    /**
     * Ver logs de la sesión actual de selenium.
     * @FOSRest\Get("/logs/selenium")
     */
    public function logsSeleniumAction(Request $request)
    {
        $em = $this->container->get("doctrine.orm.entity_manager");
        $sessionId = $em->createQueryBuilder()
            ->select('s')
            ->from('App:BotSession', 's')
            ->setMaxResults(1)
            ->orderBy('s.id', 'DESC')
            ->getQuery()->getSingleResult()->getId();
        try {
            $selLogs = file_get_contents("/var/www/debug/Selenium/$sessionId/sel.log");
            return $this->container->get("response")->success("LOADED", $selLogs);
        } catch (\Exception $e) {
            $this->get("app.dblogger")->success("No se han podido cargar los logs de selenium: " . $e->getMessage());
            return $this->container->get("response")->error(500, "SELENIUM_SESSION_LOGFILE_ERROR");
        }

    }

    /**
     * Ver logs de la sesión seleccionada de selenium.
     * @FOSRest\Get("/logs/selenium/{sessionId}")
     */
    public function logsSeleniumFilterAction(Request $request)
    {
        $em = $this->container->get("doctrine.orm.entity_manager");
        $sessionId = $request->get("sessionId");
        try {
            $selLogs = file_get_contents("/var/www/debug/Selenium/$sessionId/sel.log");
            return $this->container->get("response")->success("LOADED", $selLogs);
        } catch (\Exception $e) {
            $this->get("app.dblogger")->success("No se han podido cargar los logs de selenium: " . $e->getMessage());
            return $this->container->get("response")->error(500, "SELENIUM_SESSION_LOGFILE_ERROR");
        }

    }

    /**
     * Ver id de la última sesión.
     * Si el bot está online, la última sesión es la actual.
     * @FOSRest\Get("/session")
     */
    public function sessionAction(Request $request)
    {
        $em = $this->container->get("doctrine.orm.entity_manager");
        $sessionId = $em->createQueryBuilder()
            ->select('s')
            ->from('App:BotSession', 's')
            ->setMaxResults(1)
            ->orderBy('s.id', 'DESC')
            ->getQuery()->getSingleResult();
        return $this->container->get("response")->success("LAST_SESSION_ID", $sessionId);
    }

    /**
     * Ver estado del bot.
     * @FOSRest\Get("/status")
     */
    public function statusAction(Request $request)
    {
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
        return $this->container->get("response")->success($serverStatusName);
    }

    /**
     * Iniciar bot.
     * @FOSRest\Post("/start")
     */
    public function startBot(Request $request)
    {
        $em = $this->get("doctrine.orm.entity_manager");
        /*
         * Iniciar estado del servidor.
         * Dejar sólo un estado del bot (prevenir duplicados).
         * Marcar el estado como running, y resetear
         * los demás valores.
         */

        $em->createQueryBuilder()
            ->delete('App:ServerStatus', 's')
            ->where('s.id != :serverId')
            ->setParameter('serverId', 1)
            ->getQuery()->execute();

        $bootServer = $em->getRepository("App:ServerStatus")->findOneBy(['id' => 1]);
        if ($bootServer === null) {
            $bootServer = new ServerStatus();
        }

        $bootServer->setId(1);
        $bootServer->setCurrentStatus($em->getRepository("App:ServerStatusOptions")->findOneBy(['status' => 'BOOTING']));
        $bootServer->setSessionAlerts(0);
        $bootServer->setSessionErrors(0);
        $bootServer->setSessionProcessedRequests(0);
        $bootServer->setSessionWarnings(0);
        $em->persist($bootServer);
        $em->flush();

        $ssh = $this->get("app.ssh");
        if (!$ssh->connect()) {
            return $this->container->get("response")->error(500, "SERVER_NOT_CONFIGURED");
        }

        /*
         * Iniciar sesión del bot.
         */
        $botSession = new BotSession();
        $botSession->setDatetime();
        $em->persist($botSession);
        $em->flush();
        /*
         * Matar todos procesos el bot que estuvieran corriendo antes.
         */
        $ssh->killBotProcess();

        /*
         * Establecer modo headless.
         */
        $ssh->setBotHeadless(true);

        /*
         * Abrir interfaz gráfica.
         */
        $ssh->startX();

        /*
         * Iniciar el bot y selenium.
         */
        $ssh->startSelenium();
        $ssh->startBot();

        $this->get("app.dblogger")->success("Servidor iniciado.");
        $ssh->disconnect();
        return $this->container->get("response")->success("SERVER_STARTED");
    }

    /**
     * Cerrar bot.
     * @FOSRest\Post("/close")
     */
    public function closeBot(Request $request)
    {
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
        return $this->container->get("response")->success("SERVER_CLOSED");
    }
}