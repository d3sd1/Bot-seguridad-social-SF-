<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;
use JMS\Serializer\Annotation as JMS;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * @ORM\Entity()
 * @JMS\ExclusionPolicy("none")
 */
class ConsultaAltasCcc extends Consulta
{

    /**
     * Tipo de empresa.
     * @JMS\Type("string")
     * @ORM\ManyToOne(targetEntity="App\Entity\ContractAccounts")
     * @ORM\JoinColumn(referencedColumnName="name")
     */
    private $cca = null;

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