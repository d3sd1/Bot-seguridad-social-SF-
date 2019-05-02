<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;
use JMS\Serializer\Annotation as JMS;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * @ORM\Entity()
 * @JMS\ExclusionPolicy("none")
 */
class Queue
{
    /**
     * @ORM\Id()
     * @ORM\GeneratedValue()
     * @ORM\Column(type="integer")
     */
    private $id;

    /**
     * @ORM\ManyToOne(targetEntity="ProcessType")
     * @ORM\JoinColumn(referencedColumnName="id")
     */
    private $processType;

    /**
     * @ORM\Column(type="integer",nullable=false,unique=true)
     */
    private $referenceId;


    /**
     * Fecha de recepción de la operación
     * @ORM\Column(type="datetime")
     */
    private $dateAdded;

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
    public function getProcessType()
    {
        return $this->processType;
    }

    /**
     * @param mixed $processType
     */
    public function setProcessType($processType): void
    {
        $this->processType = $processType;
    }

    /**
     * @return mixed
     */
    public function getDateAdded()
    {
        return $this->dateAdded;
    }

    /**
     * @param mixed $dateAdded
     */
    public function setDateAdded()
    {
        $this->dateAdded = new \DateTime("now");
    }

    /**
     * @return mixed
     */
    public function getReferenceId()
    {
        return $this->referenceId;
    }

    /**
     * @param mixed $referenceId
     */
    public function setReferenceId($referenceId): void
    {
        $this->referenceId = $referenceId;
    }

}