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
        $this->container->get("app.dblogger")->warning("Llamada al rest TRACE: [" . $exception->getTraceAsString() . "] EXCEPTION: [" . $exception->getMessage() . "]");
        switch (ltrim(get_class($exception), "\\")) {
            case "JMS\Serializer\Exception\RuntimeException":
                return "INVALID_OBJECT";
                break;
            default:
                return "UNCAUGHT_EXCEPTION";
        }
    }
}