<?php

namespace App\Utils;

use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use phpseclib\Net\SSH2;

/*
 * Clase para simplificar las excepciones del la capa REST (Controller)
 */

class ExceptionManager
{

    private $container;
    private $em;

    public function __construct(ContainerInterface $container, EntityManagerInterface $em)
    {
        $this->container = $container;
        $this->em = $em;
    }


    /*
     * Esto es el manager de las excepciones capturadas.
     * Aquí es donde se introduce el log a la base de datos.
     * También, si es necesario, se reinicia el servidor.
     * Devuelve el código del REST asociado al error.
     */
    public function capture(\Exception $exception)
    {
        $server = $this->em->getRepository("App:ServerStatus")->findAll()[0];
        $this->container->get("app.dblogger")->error("Llamada al rest TRACE: [" . $exception->getTraceAsString() . "] EXCEPTION: [" . $exception->getMessage() . "]");
        switch (ltrim(get_class($exception), "\\")) {
            case "JMS\Serializer\Exception\RuntimeException":
                return "INVALID_OBJECT";
                break;
            case "App\Exceptions\SocketCommunicationException":
                /*
                 * Comprobar si la opción de reinicio automático está activada.
                 */
                if (getenv("FORCE_SERVER_RELOAD") == true) {
                    /*
                     * Encender el servidor. Antes de iniciarlo lo apaga, así que sin problema. Pero hay que comprobar que no esté en "booting" (prevenir loops).

                    if ($server->getCurrentStatus() == "BOOTING") {
                        $this->container->get("app.dblogger")->error("REINICIANDO SERVIDOR FORZADAMENTE POR MOTIVOS TÉCNICOS (AUTOMÁTICO).");
                        $this->container->get("bot.manager")->close();
                        $this->container->get("bot.manager")->start();
                    } else {
                        $this->container->get("app.dblogger")->error("ESPERANDO REINICIO SERVIDOR (AUTOMÁTICO).");
                    } */
                }
                return "SOCKETS_OFFLINE";
                break;
            default:
                return "UNCAUGHT_EXCEPTION";
        }
    }
}