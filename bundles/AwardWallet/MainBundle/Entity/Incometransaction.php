<?php

namespace AwardWallet\MainBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * Incometransaction.
 *
 * @ORM\Table(name="IncomeTransaction")
 * @ORM\Entity
 */
class Incometransaction
{
    /**
     * @var int
     * @ORM\Column(name="IncomeTransactionID", type="integer", nullable=false)
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="IDENTITY")
     */
    protected $incometransactionid;

    /**
     * @var \DateTime
     * @ORM\Column(name="Date", type="datetime", nullable=false)
     */
    protected $date;

    /**
     * @var bool
     * @ORM\Column(name="Processed", type="boolean", nullable=true)
     */
    protected $processed = false;

    /**
     * @var string
     * @ORM\Column(name="Description", type="string", length=2000, nullable=false)
     */
    protected $description;

    /**
     * Get incometransactionid.
     *
     * @return int
     */
    public function getIncometransactionid()
    {
        return $this->incometransactionid;
    }

    /**
     * Set date.
     *
     * @param \DateTime $date
     * @return Incometransaction
     */
    public function setDate($date)
    {
        $this->date = $date;

        return $this;
    }

    /**
     * Get date.
     *
     * @return \DateTime
     */
    public function getDate()
    {
        return $this->date;
    }

    /**
     * Set processed.
     *
     * @param bool $processed
     * @return Incometransaction
     */
    public function setProcessed($processed)
    {
        $this->processed = $processed;

        return $this;
    }

    /**
     * Get processed.
     *
     * @return bool
     */
    public function getProcessed()
    {
        return $this->processed;
    }

    /**
     * Set description.
     *
     * @param string $description
     * @return Incometransaction
     */
    public function setDescription($description)
    {
        $this->description = $description;

        return $this;
    }

    /**
     * Get description.
     *
     * @return string
     */
    public function getDescription()
    {
        return $this->description;
    }
}
