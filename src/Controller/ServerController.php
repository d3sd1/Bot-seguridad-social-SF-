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
 * @Route("/server")
 */
class ServerController extends Controller
{

    /**
     * Iniciar bot.
     * @FOSRest\Post("/restart")
     */
    public function startBot(Request $request)
    {
        $this->get("app.dblogger")->success("Petición de reinicio servidor (SISTEMA OPERATIVO) recibida correctamente.");
        $this->get("bot.manager")->restartServerSO();
        return $this->container->get("response")->success("SERVER_SO_RESTARTED");
    }

}