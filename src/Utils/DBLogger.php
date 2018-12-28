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
        $recipers = explode(',',getenv('LOG_EMAILS'));
        $log = new InternalLog();
        $log->setMessage("Sending error messages to " .getenv('LOG_EMAILS'));
        $log->setType($this->em->getRepository("App:LogType")->findOneBy(['type' => $type]));
        $this->em->persist($log);

        foreach($recipers as $reciper) {
            $log = new InternalLog();
            $log->setMessage("Sent error message to " .$reciper);
            $log->setType($this->em->getRepository("App:LogType")->findOneBy(['type' => $type]));
            $this->em->persist($log);

            $message = (new \Swift_Message('('.$type.') En bot de la seguridad social'))
                ->setFrom('bot-ss@workout-events.com')
                ->setTo($reciper)
                ->setBody(
                    'El bot de la seguridad social (192.168.1.32) ha tenido una excepción: '.$msg,
                    'text/html'
                );


            $transport = (new \Swift_SmtpTransport('email-smtp.us-west-2.amazonaws.com', 587, 'tls'))
                ->setUsername('AKIAI7M6LEJO4FQROWTQ')
                ->setPassword('AuyAV0Zirt+lK47RwE1nKinH5aWt/ysH2N1ZB85NRmiJ')
            ;

            $mailer = new \Swift_Mailer($transport);
            $mailer->send($message);
        }
        $this->em->flush();

    }

    public function error($msg)
    {
        $log = new InternalLog();
        $log->setMessage(strip_tags($msg));
        $log->setType($this->em->getRepository("App:LogType")->findOneBy(['type' => 'ERROR']));
        $this->em->persist($log);
        $this->sendErrorMail('ERROR',$msg);
        $this->em->flush();
    }

    public function warning($msg)
    {
        $log = new InternalLog();
        $log->setMessage(strip_tags($msg));
        $log->setType($this->em->getRepository("App:LogType")->findOneBy(['type' => 'WARNING']));
        $this->em->persist($log);
        $this->em->flush();
    }

    public function info($msg)
    {
        $log = new InternalLog();
        $log->setMessage(strip_tags($msg));
        $log->setType($this->em->getRepository("App:LogType")->findOneBy(['type' => 'INFO']));
        $this->em->persist($log);
        $this->em->flush();
    }

    public function success($msg)
    {
        $log = new InternalLog();
        $log->setMessage(strip_tags($msg));
        $log->setType($this->em->getRepository("App:LogType")->findOneBy(['type' => 'SUCCESS']));
        $this->em->persist($log);
        $this->em->flush();
    }
}