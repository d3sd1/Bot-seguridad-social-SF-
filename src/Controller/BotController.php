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
            ->setMaxResults(200)
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
            ->setMaxResults(200)
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
            //$this->get("app.dblogger")->success("No se han podido cargar los logs de selenium: " . $e->getMessage());
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
            //$this->get("app.dblogger")->success("No se han podido cargar los logs de selenium: " . $e->getMessage());
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
        return $this->container->get("response")->success($this->get("bot.manager")->getBotStatus());
    }

    /**
     * Iniciar bot.
     * @FOSRest\Post("/start")
     */
    public function startBot(Request $request)
    {
        if ($this->get("bot.manager")->start(true)) {
            $this->get("app.dblogger")->success("Servidor iniciado correctamente.");
            $this->container->get("so.commands")->startBot();
            return $this->container->get("response")->success("SERVER_STARTED");
        } else {
            $this->get("app.dblogger")->success("El servidor no se pudo iniciar. Para más información lanza el bot en modo debug GUI-BASH.");
            return $this->container->get("response")->success("SERVER_NOT_STARTED");
        }
    }

    /**
     * Cerrar bot.
     * @FOSRest\Post("/close")
     */
    public function closeBot(Request $request)
    {

        if ($this->get("bot.manager")->close()) {
            $this->get("app.dblogger")->success("Servidor detenido correctamente.");
            return $this->container->get("response")->success("SERVER_CLOSED");
        } else {
            $this->get("app.dblogger")->success("El servidor no se pudo iniciar. Para más información lanza el bot en modo debug GUI-BASH.");
            return $this->container->get("response")->success("SERVER_NOT_CLOSED");
        }
    }

    /**
     * Estudio de tiempos de procesamiento.
     * @FOSRest\Get("/timings")
     */
    public function avgTimeBot(Request $request)
    {
        $result = new \stdClass();

        // Para añadir más operaciones al estudio, añadir aquí el nombre de su entidad respetando mayus/minus.
        $operations = ["Alta", "Baja"];

        foreach ($operations as $op) {

            $result->{$op} = new \stdClass();
            $result->{$op}->count = $this->getDoctrine()
                ->getRepository('App:' . $op)->createQueryBuilder('op')
                ->select('count(op.id)')
                ->getQuery()->getSingleScalarResult();
            $result->{$op}->avg = $this->getDoctrine()
                ->getRepository('App:' . $op)->createQueryBuilder('op')
                ->select("avg(op.processTime)")
                ->where('op.processTime != :pt')
                ->setParameter('pt', 0)
                ->getQuery()->getResult()[0][1];

            $result->{$op}->max = $this->getDoctrine()
                ->getRepository('App:' . $op)->createQueryBuilder('op')
                ->select("max(op.processTime)")
                ->where('op.processTime != :pt')
                ->setParameter('pt', 0)
                ->getQuery()->getResult()[0][1];
            $result->{$op}->min = $this->getDoctrine()
                ->getRepository('App:' . $op)->createQueryBuilder('op')
                ->select("min(op.processTime)")
                ->where('op.processTime != :pt')
                ->setParameter('pt', 0)
                ->getQuery()->getResult()[0][1];
        }

        return $this->container->get("response")->success("RESULT", json_encode($result));
    }

    /**
     * Estudio de resultados de procesamiento.
     * @FOSRest\Get("/results")
     */
    public function resultsBot(Request $request)
    {
        $result = new \stdClass();

        // Para añadir más operaciones al estudio, añadir aquí el nombre de su entidad respetando mayus/minus.
        $operations = ["Alta", "Baja"];

        foreach ($operations as $op) {

            $result->{$op} = new \stdClass();
            $result->{$op}->total = $this->getDoctrine()
                ->getRepository('App:' . $op)->createQueryBuilder('op')
                ->select('count(op.id)')
                ->getQuery()->getSingleScalarResult();
            $result->{$op}->errors = $this->getDoctrine()
                ->getRepository('App:' . $op)->createQueryBuilder('op')
                ->select('count(op.id)')
                ->where('op.status = 5')
                ->getQuery()->getSingleScalarResult();
            $result->{$op}->success = $result->{$op}->total - $result->{$op}->errors;
            $result->{$op}->errorPercentage = $result->{$op}->errors / $result->{$op}->total * 100;
        }

        return $this->container->get("response")->success("RESULT", json_encode($result));
    }
}
