<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity()
 */
class ServerStatus
{
    /**
     * @ORM\Id()
     * @ORM\GeneratedValue()
     * @ORM\Column(type="integer")
     */
    private $id;

    /**
     * @ORM\ManyToOne(targetEntity="ServerStatusOptions")
     * @ORM\JoinColumn(referencedColumnName="id")
     */
    private $currentStatus;

    /**
     * @ORM\Column(type="integer",options={"default": 0})
     */
    private $sessionWarnings;

    /**
     * @ORM\Column(type="integer",options={"default": 0})
     */
    private $sessionAlerts;

    /**
     * @ORM\Column(type="integer",options={"default": 0})
     */
    private $sessionErrors;

    /**
     * @ORM\Column(type="integer",options={"default": 0})
     */
    private $sessionProcessedRequests;

    /**
     * @ORM\Column(type="boolean",options={"default": false})
     */
    private $crashPrevented;

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
    public function getCurrentStatus()
    {
        return $this->currentStatus;
    }

    /**
     * @param mixed $currentStatus
     */
    public function setCurrentStatus($currentStatus): void
    {
        $this->currentStatus = $currentStatus;
    }

    /**
     * @return mixed
     */
    public function getSessionWarnings()
    {
        return $this->sessionWarnings;
    }

    /**
     * @param mixed $sessionWarnings
     */
    public function setSessionWarnings($sessionWarnings): void
    {
        $this->sessionWarnings = $sessionWarnings;
    }

    /**
     * @return mixed
     */
    public function getSessionAlerts()
    {
        return $this->sessionAlerts;
    }

    /**
     * @param mixed $sessionAlerts
     */
    public function setSessionAlerts($sessionAlerts): void
    {
        $this->sessionAlerts = $sessionAlerts;
    }

    /**
     * @return mixed
     */
    public function getSessionErrors()
    {
        return $this->sessionErrors;
    }

    /**
     * @param mixed $sessionErrors
     */
    public function setSessionErrors($sessionErrors): void
    {
        $this->sessionErrors = $sessionErrors;
    }

    /**
     * @return mixed
     */
    public function getSessionProcessedRequests()
    {
        return $this->sessionProcessedRequests;
    }

    /**
     * @param mixed $sessionProcessedRequests
     */
    public function setSessionProcessedRequests($sessionProcessedRequests): void
    {
        $this->sessionProcessedRequests = $sessionProcessedRequests;
    }

    /**
     * @return mixed
     */
    public function getCrashPrevented()
    {
        return $this->crashPrevented;
    }

    /**
     * @param mixed $crashPrevented
     */
    public function setCrashPrevented($crashPrevented): void
    {
        $this->crashPrevented = $crashPrevented;
    }

}
