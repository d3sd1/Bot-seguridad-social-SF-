<?php

namespace App\Utils;

use App\Entity\InternalLog;
use Doctrine\ORM\EntityManagerInterface;

class DBLogger
{

    private $em;

    public function __construct(EntityManagerInterface $em)
    {
        $this->em = $em;
    }

    public function error($msg)
    {
        $log = new InternalLog();
        $log->setMessage($msg);
        $log->setType($this->em->getRepository("App:LogType")->findOneBy(['type' => 'ERROR']));
        $this->em->persist($log);
        $this->em->flush();
    }

    public function warning($msg)
    {
        $log = new InternalLog();
        $log->setMessage($msg);
        $log->setType($this->em->getRepository("App:LogType")->findOneBy(['type' => 'WARNING']));
        $this->em->persist($log);
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