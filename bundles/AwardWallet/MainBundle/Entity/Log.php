<?php

namespace AwardWallet\MainBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * Log.
 *
 * @ORM\Table(name="log")
 * @ORM\Entity
 */
class Log
{
    /**
     * @var int
     * @ORM\Column(name="LogID", type="integer", nullable=false)
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="IDENTITY")
     */
    protected $logid;

    /**
     * @var string
     * @ORM\Column(name="Source", type="string", length=20, nullable=false)
     */
    protected $source;

    /**
     * @var string
     * @ORM\Column(name="Operation", type="string", length=20, nullable=false)
     */
    protected $operation;

    /**
     * @var \DateTime
     * @ORM\Column(name="LogDate", type="datetime", nullable=false)
     */
    protected $logdate;

    /**
     * @var string
     * @ORM\Column(name="Details", type="string", length=128, nullable=true)
     */
    protected $details;

    public function __construct()
    {
        $this->logdate = new \DateTime();
    }

    /**
     * Get logid.
     *
     * @return int
     */
    public function getLogid()
    {
        return $this->logid;
    }

    /**
     * Set source.
     *
     * @param string $source
     * @return Log
     */
    public function setSource($source)
    {
        $this->source = $source;

        return $this;
    }

    /**
     * Get source.
     *
     * @return string
     */
    public function getSource()
    {
        return $this->source;
    }

    /**
     * Set operation.
     *
     * @param string $operation
     * @return Log
     */
    public function setOperation($operation)
    {
        $this->operation = $operation;

        return $this;
    }

    /**
     * Get operation.
     *
     * @return string
     */
    public function getOperation()
    {
        return $this->operation;
    }

    /**
     * Set logdate.
     *
     * @param \DateTime $logdate
     * @return Log
     */
    public function setLogdate($logdate)
    {
        $this->logdate = $logdate;

        return $this;
    }

    /**
     * Get logdate.
     *
     * @return \DateTime
     */
    public function getLogdate()
    {
        return $this->logdate;
    }

    /**
     * Set details.
     *
     * @param string $details
     * @return Log
     */
    public function setDetails($details)
    {
        $this->details = $details;

        return $this;
    }

    /**
     * Get details.
     *
     * @return string
     */
    public function getDetails()
    {
        return $this->details;
    }
}
