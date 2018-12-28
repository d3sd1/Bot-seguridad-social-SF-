<?php

namespace App\Utils;

use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\JsonResponse;

class RestResponse
{
    private $container;

    function __construct(ContainerInterface $container)
    {
        $this->container = $container;
    }

    function error(int $code, String $message)
    {
        $this->container->get("bot.manager")->preventHanging();

        $response = new JsonResponse();
        $response->setStatusCode($code);
        $response->setContent($this->container->get('jms_serializer')->serialize(array("message" => $message != null ? $message : ""), "json"));
        return $response;
    }

    function success($message = null, $data = null)
    {
        $this->container->get("bot.manager")->preventHanging();

        $response = new JsonResponse();
        $response->setStatusCode(200);
        $response->setContent($this->container->get('jms_serializer')->serialize(array("message" => $message != null ? $message : "", "data" => $data != null ? $data : ""), "json"));
        return $response;
    }

    function informative($message = null, $data = null)
    {
        $this->container->get("bot.manager")->preventHanging();

        $response = new JsonResponse();
        $response->setStatusCode(100);
        $response->setContent($this->container->get('jms_serializer')->serialize(array("message" => $message != null ? $message : "", "data" => $data != null ? $data : ""), "json"));
        return $response;
    }
}
