<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;
use JMS\Serializer\Annotation as JMS;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * @ORM\Entity()
 * @JMS\ExclusionPolicy("none")
 */
class ContractKey
{

    /**
     * @ORM\Id()
     * @ORM\Column(type="integer",unique=true,nullable=false,length=3)
     */
    private $ckey;

    /**
     * @ORM\ManyToOne(targetEntity="App\Entity\ContractType")
     * @ORM\JoinColumn(referencedColumnName="id")
     * @ORM\Column(nullable=false)
     */
    private $type;

    /**
     * @ORM\ManyToOne(targetEntity="App\Entity\ContractTimeType")
     * @ORM\JoinColumn(referencedColumnName="id")
     * @ORM\Column(nullable=false)
     */
    private $timeType;

    /**
     * @ORM\Column(type="string",nullable=true)
     */
    private $description;

    /**
     * @return mixed
     */
    public function getCkey()
    {
        return $this->ckey;
    }

    /**
     * @param mixed $ckey
     */
    public function setCkey($ckey): void
    {
        $this->ckey = $ckey;
    }

    /**
     * @return mixed
     */
    public function getType()
    {
        return $this->type;
    }

    /**
     * @param mixed $type
     */
    public function setType($type): void
    {
        $this->type = $type;
    }

    /**
     * @return mixed
     */
    public function getDescription()
    {
        return $this->description;
    }

    /**
     * @param mixed $description
     */
    public function setDescription($description): void
    {
        $this->description = $description;
    }

    /**
     * @return mixed
     */
    public function getTimeType()
    {
        return $this->timeType;
    }

    /**
     * @param mixed $timeType
     */
    public function setTimeType($timeType): void
    {
        $this->timeType = $timeType;
    }


}