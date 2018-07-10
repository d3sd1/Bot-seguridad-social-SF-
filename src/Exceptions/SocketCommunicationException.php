<?php
/**
 * Created by PhpStorm.
 * User: Andrei
 * Date: 04/06/2018
 * Time: 18:05
 */

namespace App\Exceptions;


class SocketCommunicationException extends \Exception
{
    public function __construct($message)
    {
        parent::__construct($message);
    }
}