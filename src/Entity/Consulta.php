<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;
use JMS\Serializer\Annotation as JMS;
use Symfony\Component\Validator\Constraints as Assert;

/**  */

/**
 * @ORM\MappedSuperclass()
 * @JMS\ExclusionPolicy("none")
 */
class Consulta extends Operation
{
    /**
     * Contiene los datos relacionados a la consulta si fue satisfactoria.
     * @JMS\Exclude(if="context.getDirection() === 0")
     * @JMS\Type("string")
     * @ORM\Column(type="string", unique=false, nullable=true)
     */
    private $data = null;

    /**
     * @return mixed
     */
    public function getData()
    {
        return $this->data;
    }

    /**
     * @param mixed $data
     */
    public function setData($data): void
    {
        $this->data = $data;
    }


}