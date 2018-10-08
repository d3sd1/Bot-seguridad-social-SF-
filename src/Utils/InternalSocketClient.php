<?php

namespace App\Utils;

use mikehaertl\shellcommand\Command;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\Form\Extension\Core\Type\HiddenType;

class InternalSocketClient
{

    private $con = false;
    private $container;

    public function __construct(ContainerInterface $container)
    {
        $this->container = $container;
    }

    private function connect()
    {
        if ($this->con === false) {
            try {
                $this->con = socket_create(AF_INET, SOCK_STREAM, 0) or die("Could not create socket\n");
                $status = socket_connect($this->con, getenv("INTERNAL_SOCKETS_HOST"), getenv("INTERNAL_SOCKETS_PORT"));
            } catch (\Exception $e) {
                $this->container->get("app.exception")->capture(new \App\Exceptions\SocketCommunicationException("Could not connect trought internal sockets. Server offline: " . $e->getMessage()));
            }
        }
    }

    /*
     * Esta función envía datos al servidor de sockets.
     */
    public function send($data)
    {
        $this->connect();
        try {
            socket_write($this->con, $data, strlen($data));
            $this->close();
        } catch (\Exception $e) {
            $this->container->get("app.exception")->capture(new \App\Exceptions\SocketCommunicationException("Could not sent data trought internal sockets. Server busy." . $e->getMessage()));
        }
    }

    /*
     * Esta función notifica al servidor que
     * hay acciones por resolver.
     */
    public function notify()
    {
        $this->send("NOTIFY");
    }

    private function close()
    {
        socket_close($this->con);
    }

}