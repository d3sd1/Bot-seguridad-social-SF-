<?php

namespace App\Command;
set_time_limit(0);

use Doctrine\ORM\EntityManager;
use Symfony\Component\DependencyInjection\ContainerInterface;

class SocketServer
{
    private $socket = false;
    private $em;
    private $container;

    public function __construct(EntityManager $em, ContainerInterface $container)
    {
        $this->em = $em;
        $this->container = $container;
    }

    /*
     * Iniciar escuchadores y prevenir que se deniegue la ejecución.
     */
    public function startSockets()
    {
        if ($this->socket === false) {
            try {
                $this->socket = socket_create(AF_INET, SOCK_STREAM, 0);
                socket_set_option($this->socket, SOL_SOCKET, SO_REUSEADDR, 1);
                socket_set_option($this->socket, SOL_SOCKET, SO_RCVTIMEO, array('sec' => 0, 'usec' => 0));
                socket_set_option($this->socket, SOL_SOCKET, SO_SNDTIMEO, array('sec' => 0, 'usec' => 0));
                socket_bind($this->socket, getenv("INTERNAL_SOCKETS_HOST"), getenv("INTERNAL_SOCKETS_PORT"));
                socket_listen($this->socket, 3);
            } catch (\Exception $e) {
                $this->container->get("app.dblogger")->error("El servidor ya estaba iniciado.");
                exit();
            }
        }
        return $this->socket;
    }

    /*
     * Cerrar escuchadores.
     */
    public function closeSockets()
    {
        socket_close($this->socket);
    }

    /*
     * Esperar a operación por sockets.
     */
    public function waitForTask()
    {
        try {
            $spawn = socket_accept($this->socket);
            $input = socket_read($spawn, 1024);
            $data = trim($input);
            socket_close($spawn);
        } catch (\Exception $e) {
            return false;
        }
        return true;
    }
}