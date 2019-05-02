<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity()
 */
class ContractAccounts
{
    /**
     * Nombre clave para la empresa
     * @ORM\Id()
     * @ORM\Column(type="string", unique=true, nullable=false)
     */
    private $name = null;

    /**
     * @ORM\Column(type="bigint", unique=true, columnDefinition="BIGINT(11) UNSIGNED ZEROFILL")
     */
    private $ccc = null;

    /**
     * Régimen
     * @ORM\Column(type="integer", columnDefinition="INT(4) UNSIGNED ZEROFILL")
     */
    private $reg = null;


    /**
     * @return mixed
     */
    public function getCcc()
    {
        return $this->ccc;
    }

    /**
     * @param mixed $ccc
     */
    public function setCcc($ccc): void
    {
        $this->ccc = $ccc;
    }

    /**
     * @return mixed
     */
    public function getReg()
    {
        return $this->reg;
    }

    /**
     * @param mixed $reg
     */
    public function setReg($reg): void
    {
        $this->reg = $reg;
    }

    /**
     * @return mixed
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * @param mixed $name
     */
    public function setName($name): void
    {
        $this->name = $name;
    }

}
