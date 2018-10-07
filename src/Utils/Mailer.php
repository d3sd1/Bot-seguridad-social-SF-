<?php

class Mailer
{
    public function index($msg)
    {
        $message = (new \Swift_Message('El bot de la seguridad social ha fallado'))
            ->setFrom('send@example.com')
            ->setTo('recipient@example.com')
            ->setBody($msg)
        ;

        (new \Swift_Mailer)->send($message);

        return $this->render(...);
    }
}