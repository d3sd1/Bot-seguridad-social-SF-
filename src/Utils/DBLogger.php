<?php

namespace App\Utils;

use App\Entity\InternalLog;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Container\ContainerInterface;

class DBLogger
{

    private $em;
    private $container;

    public function __construct(EntityManagerInterface $em, ContainerInterface $container)
    {
        $this->em = $em;
        $this->container = $container;
    }

    public function sendErrorMail($type,$msg) {
        $message = (new \Swift_Message('('.$type.') MError en bot de la seguridad social'))
            ->setFrom('ss-bot@workout-events.com')
            ->setBody(
                'El bot de la seguridad social (192.168.1.32) ha tenido una excepción: ' + $msg,
                'text/html'
            );
        $recipers = explode(',',getenv('LOG_EMAILS'));
        foreach($recipers as $reciper) {
            $message->setTo($reciper);
        }
        $this->container->get('mailer')->send($message);
    }

    public function error($msg)
    {
        $log = new InternalLog();
        $log->setMessage($msg);
        $log->setType($this->em->getRepository("App:LogType")->findOneBy(['type' => 'ERROR']));
        $this->em->persist($log);
        $this->container->get('mailer')->send('ERROR',$msg);
        $this->em->flush();
    }

    public function warning($msg)
    {
        $log = new InternalLog();
        $log->setMessage($msg);
        $log->setType($this->em->getRepository("App:LogType")->findOneBy(['type' => 'WARNING']));
        $this->em->persist($log);
        $this->container->get('mailer')->send('Warning',$msg);
        $this->em->flush();
    }

    public function info($msg)
    {
        $log = new InternalLog();
        $log->setMessage($msg);
        $log->setType($this->em->getRepository("App:LogType")->findOneBy(['type' => 'INFO']));
        $this->em->persist($log);
        $this->em->flush();
    }

    public function success($msg)
    {
        $log = new InternalLog();
        $log->setMessage($msg);
        $log->setType($this->em->getRepository("App:LogType")->findOneBy(['type' => 'SUCCESS']));
        $this->em->persist($log);
        $this->em->flush();
    }
}