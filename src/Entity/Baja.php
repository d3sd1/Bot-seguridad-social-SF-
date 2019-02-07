<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;
use JMS\Serializer\Annotation as JMS;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * @ORM\Entity()
 * @JMS\ExclusionPolicy("none")
 */
class Baja extends Operation
{

    /**
     * Tipo de empresa.
     * @JMS\Type("string")
     * @ORM\ManyToOne(targetEntity="App\Entity\ContractAccounts")
     * @ORM\JoinColumn(referencedColumnName="name")
     */
    private $cca = null;

    /**
     * Número de afiliación.
     * @JMS\Type("string")
     * @Assert\NotBlank()
     * @Assert\Length(min=12,max=12)
     * @ORM\Column(type="bigint", columnDefinition="BIGINT(12) UNSIGNED ZEROFILL")
     */
    private $naf = null;

    /**
     * Identificación de personas físicas.
     * @JMS\Type("string")
     * @Assert\Length(min=5,max=15)
     * @ORM\Column(type="string")
     */
    private $ipf = null;

    /**
     * Identificación de personas físicas.
     * @JMS\Type("string")
     * @Assert\Length(min=2,max=2)
     * @ORM\Column(type="integer", columnDefinition="INT(2) UNSIGNED ZEROFILL")
     */
    private $ipt = null;


    /**
     * Situación
     * @JMS\Type("string")
     * @Assert\Length(min=2,max=2)
     * @ORM\Column(type="integer", columnDefinition="INT(2) UNSIGNED ZEROFILL")
     */
    private $sit = null;

    /**
     * Fecha real de la baja
     * @JMS\Type("DateTime<'Y-m-d','','|Y-m-d'>")
     * @ORM\Column(type="datetime")
     */
    private $frb = null;


    /**
     * Fecha de fin de vacaciones.
     * @JMS\Type("DateTime<'Y-m-d','','|Y-m-d'>")
     * @ORM\Column(type="datetime", nullable=true)
     */
    private $ffv = null;

    /**
     * @return mixed
     */
    public function getCca()
    {
        return $this->cca;
    }

    /**
     * @param mixed $cca
     */
    public function setCca($cca): void
    {
        $this->cca = $cca;
    }

    /**
     * @return mixed
     */
    public function getNaf()
    {
        return $this->naf;
    }

    /**
     * @param mixed $naf
     */
    public function setNaf($naf): void
    {
        $this->naf = $naf;
    }

    /**
     * @return mixed
     */
    public function getIpf()
    {
        return $this->ipf;
    }

    /**
     * @param mixed $ipf
     */
    public function setIpf($ipf): void
    {
        $this->ipf = $ipf;
    }

    /**
     * @return mixed
     */
    public function getIpt()
    {
        return $this->ipt;
    }

    /**
     * @param mixed $ipt
     */
    public function setIpt($ipt): void
    {
        $this->ipt = $ipt;
    }

    /**
     * @return mixed
     */
    public function getSit()
    {
        return $this->sit;
    }

    /**
     * @param mixed $sit
     */
    public function setSit($sit): void
    {
        $this->sit = $sit;
    }

    /**
     * @return mixed
     */
    public function getFrb()
    {
        return $this->frb;
    }

    /**
     * @param mixed $frb
     */
    public function setFrb($frb): void
    {
        $this->frb = $frb;
    }

    /**
     * @return mixed
     */
    public function getFfv()
    {
        return $this->ffv;
    }

    /**
     * @param mixed $ffv
     */
    public function setFfv($ffv): void
    {
        $this->ffv = $ffv;
    }

}