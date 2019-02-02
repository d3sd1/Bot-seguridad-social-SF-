<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;
use JMS\Serializer\Annotation as JMS;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * @ORM\Entity()
 * @JMS\ExclusionPolicy("none")
 */
class ConsultaTa extends Consulta
{

    /**
     * Número de afiliación.
     * @JMS\Type("string")
     * @Assert\NotBlank()
     * @Assert\Length(min=12,max=12)
     * @ORM\Column(type="bigint", columnDefinition="BIGINT(12) UNSIGNED ZEROFILL")
     */
    private $naf = null;

    /**
     * Tipo de empresa.
     * @JMS\Type("string")
     * @ORM\ManyToOne(targetEntity="App\Entity\ContractAccounts")
     * @ORM\JoinColumn(referencedColumnName="name")
     */
    private $cca = null;

    /**
     * Fecha real de la consulta
     * @JMS\Type("DateTime<'Y-m-d','','|Y-m-d'>")
     * @ORM\Column(type="datetime")
     */
    private $frc = null;

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
    public function getFrc()
    {
        return $this->frc;
    }

    /**
     * @param mixed $frc
     */
    public function setFrc($frc): void
    {
        $this->frc = $frc;
    }
}