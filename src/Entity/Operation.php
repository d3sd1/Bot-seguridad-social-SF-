<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;
use JMS\Serializer\Annotation as JMS;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * @ORM\MappedSuperclass()
 * @JMS\ExclusionPolicy("none")
 */
class Operation
{

    /**
     * @JMS\Exclude(if="context.getDirection() === 0")
     * @ORM\Id()
     * @ORM\GeneratedValue()
     * @ORM\Column(type="integer")
     */
    private $id;

    /**
     * Fecha de procesamiento
     * @ORM\Column(type="datetime")
     */
    private $dateProcessed = null;

    /**
     * Fecha de recepción de la operación
     * @ORM\Column(type="datetime")
     */
    private $dateInit = null;

    /**
     * @JMS\Exclude(if="context.getDirection() === 0")
     * @ORM\ManyToOne(targetEntity="App\Entity\ProcessStatus")
     * @ORM\JoinColumn(referencedColumnName="id")
     * @ORM\Column(options={"default":4})
     */
    private $status = null;

    /**
     * Mensaje de error (si procede).
     * @JMS\Exclude(if="context.getDirection() === 0")
     * @JMS\Type("string")
     * @ORM\Column(type="string", unique=false, nullable=true)
     */
    private $errMsg = null;

    /**
     * Tiempo de procesamiento (segundos).
     * @ORM\Column(type="integer", options={"default":0})
     */
    private $processTime = null;

    /**
     * Tiempo de procesamiento (segundos).
     * @ORM\Column(type="string", nullable=true, options={"default":null})
     */
    private $callbackUrl = null;

    /**
     * Operation constructor.
     */
    public function __construct()
    {
        $this->setProcessTime(0);
    }


    /**
     * @return mixed
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * @param mixed $id
     */
    public function setId($id): void
    {
        $this->id = $id;
    }

    /**
     * @return mixed
     */
    public function getDateInit()
    {
        return $this->dateInit;
    }

    /**
     * @param mixed $dateInit
     */
    public function setDateInit()
    {
        $this->dateInit = new \DateTime("now");
    }


    /**
     * @return mixed
     */
    public function getDateProcessed()
    {
        return $this->dateProcessed;
    }

    /**
     * @param mixed $dateProcessed
     */
    public function setDateProcessed()
    {
        $this->dateProcessed = new \DateTime("now");
    }

    /**
     * @return mixed
     */
    public function getStatus()
    {
        return $this->status;
    }

    /**
     * @param mixed $status
     */
    public function setStatus($status): void
    {
        $this->status = $status;
    }

    /**
     * @return mixed
     */
    public function getErrMsg()
    {
        return $this->errMsg;
    }

    /**
     * @param mixed $errMsg
     */
    public function setErrMsg($errMsg): void
    {
        $this->errMsg = $errMsg;
    }

    /**
     * @return mixed
     */
    public function getProcessTime()
    {
        return $this->processTime;
    }

    /**
     * @param mixed $processTime
     */
    public function setProcessTime($processTime): void
    {
        $this->processTime = $processTime;
    }
    /**
     * @param mixed $processTime
     */
    public function updateProcessTime(): void
    {
        $this->processTime = (new \DateTime())->getTimestamp() - $this->getDateProcessed()->getTimestamp();
    }

    /**
     * @return mixed
     */
    public function getCallbackUrl()
    {
        return $this->callbackUrl;
    }

    /**
     * @param mixed $callbackUrl
     */
    public function setCallbackUrl($callbackUrl): void
    {
        $this->callbackUrl = $callbackUrl;
    }

}