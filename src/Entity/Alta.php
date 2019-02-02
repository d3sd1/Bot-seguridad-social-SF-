<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;
use JMS\Serializer\Annotation as JMS;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * @ORM\Entity()
 * @JMS\ExclusionPolicy("none")
 */
class Alta extends Operation
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
     * Identificación de personas físicas (Código)
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
     * Fecha real del alta
     * @JMS\Type("DateTime<'Y-m-d','','|Y-m-d'>")
     * @ORM\Column(type="datetime")
     */
    private $fra = null;


    /**
     * Grupo de cotización.
     * @JMS\Type("string")
     * @Assert\Length(min=2,max=2)
     * @ORM\Column(type="integer", columnDefinition="INT(2) UNSIGNED ZEROFILL")
     */
    private $gco = null;

    /**
     * Situación
     * @JMS\Type("string")
     * @Assert\Length(min=2,max=2)
     * @ORM\Column(type="integer", columnDefinition="INT(2) UNSIGNED ZEROFILL")
     */
    private $sit = null;

    /**
     * Tipo de contrato. Debe existir en la DB local.
     * @JMS\Type("integer")
     * @ORM\ManyToOne(targetEntity="App\Entity\ContractKey")
     * @ORM\JoinColumn(referencedColumnName="ckey")
     */
    private $tco = null;


    /**
     * Coeficiente (sólo para trabajo a tiempo parcial).
     * @JMS\Type("integer")
     * @ORM\ManyToOne(targetEntity="App\Entity\ContractCoefficient")
     * @ORM\JoinColumn(referencedColumnName="coefficient")
     */
    private $coe = null;

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
    public function getFra()
    {
        return $this->fra;
    }

    /**
     * @param mixed $fra
     */
    public function setFra($fra): void
    {
        $this->fra = $fra;
    }

    /**
     * @return mixed
     */
    public function getGco()
    {
        return $this->gco;
    }

    /**
     * @param mixed $gco
     */
    public function setGco($gco): void
    {
        $this->gco = $gco;
    }

    /**
     * @return mixed
     */
    public function getTco()
    {
        return $this->tco;
    }

    /**
     * @param mixed $tco
     */
    public function setTco($tco): void
    {
        $this->tco = $tco;
    }

    /**
     * @return mixed
     */
    public function getCoe()
    {
        return $this->coe;
    }

    /**
     * @param mixed $coe
     */
    public function setCoe($coe): void
    {
        $this->coe = $coe;
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




}