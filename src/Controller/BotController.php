<?php

namespace App\Controller;

use App\Entity\BotSession;
use App\Utils\BotSsh;
use FOS\RestBundle\Controller\Annotations as FOSRest;
use Symfony\Component\Routing\Annotation\Route;
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
        return $this->container->get("response")->success($serverStatusName);
    }

    /**
     * Iniciar bot.
     * @FOSRest\Post("/start")
     */
    public function startBot(Request $request)
    {

        $this->get("bot.manager");
        return $this->container->get("response")->success("SERVER_STARTED");
    }

    /**
     * Cerrar bot.
     * @FOSRest\Post("/close")
     */
    public function closeBot(Request $request)
    {

        return $this->container->get("response")->success("SERVER_CLOSED");
    }
}