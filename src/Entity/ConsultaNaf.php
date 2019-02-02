<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;
use JMS\Serializer\Annotation as JMS;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * @ORM\Entity()
 * @JMS\ExclusionPolicy("none")
 */
class ConsultaNaf extends Consulta
{
    /**
     * Identificación de personas físicas.
     * @JMS\Type("string")
     * @Assert\Length(min=5,max=15)
     * @ORM\Column(type="string")
     */
    private $ipf = null;

    /**
     * Identificación de personas físicas (Tipo).
     * @JMS\Type("string")
     * @Assert\Length(min=2,max=2)
     * @ORM\Column(type="integer", columnDefinition="INT(2) UNSIGNED ZEROFILL")
     */
    private $ipt = null;

    /**
     * Primer apellido.
     * @JMS\Type("string")
     * @ORM\Column(type="string")
     */
    private $ap1 = null;

    /**
     * Segundo apellido.
     * @JMS\Type("string")
     * @ORM\Column(type="string")
     */
    private $ap2 = null;

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
    public function getAp1()
    {
        return $this->ap1;
    }

    /**
     * @param mixed $ap1
     */
    public function setAp1($ap1): void
    {
        $this->ap1 = $ap1;
    }

    /**
     * @return mixed
     */
    public function getAp2()
    {
        return $this->ap2;
    }

    /**
     * @param mixed $ap2
     */
    public function setAp2($ap2): void
    {
        $this->ap2 = $ap2;
    }


}