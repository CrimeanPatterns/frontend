<?php

namespace AwardWallet\MainBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * Cancelleditinerary.
 *
 * @ORM\Table(name="CancelledItinerary")
 * @ORM\Entity
 */
class Cancelleditinerary
{
    /**
     * @var int
     * @ORM\Column(name="CancelledItineraryID", type="integer", nullable=false)
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="IDENTITY")
     */
    protected $cancelleditineraryid;

    /**
     * @var string
     * @ORM\Column(name="ConfirmationNumber", type="string", length=20, nullable=false)
     */
    protected $confirmationnumber;

    /**
     * @var string
     * @ORM\Column(name="Kind", type="string", length=1, nullable=false)
     */
    protected $kind;

    /**
     * @var \Account
     * @ORM\ManyToOne(targetEntity="Account")
     * @ORM\JoinColumns({
     *   @ORM\JoinColumn(name="AccountID", referencedColumnName="AccountID")
     * })
     */
    protected $accountid;

    /**
     * Get cancelleditineraryid.
     *
     * @return int
     */
    public function getCancelleditineraryid()
    {
        return $this->cancelleditineraryid;
    }

    /**
     * Set confirmationnumber.
     *
     * @param string $confirmationnumber
     * @return Cancelleditinerary
     */
    public function setConfirmationnumber($confirmationnumber)
    {
        $this->confirmationnumber = $confirmationnumber;

        return $this;
    }

    /**
     * Get confirmationnumber.
     *
     * @return string
     */
    public function getConfirmationnumber()
    {
        return $this->confirmationnumber;
    }

    /**
     * Set kind.
     *
     * @param string $kind
     * @return Cancelleditinerary
     */
    public function setKind($kind)
    {
        $this->kind = $kind;

        return $this;
    }

    /**
     * Get kind.
     *
     * @return string
     */
    public function getKind()
    {
        return $this->kind;
    }

    /**
     * Set accountid.
     *
     * @return Cancelleditinerary
     */
    public function setAccountid(?Account $accountid = null)
    {
        $this->accountid = $accountid;

        return $this;
    }

    /**
     * Get accountid.
     *
     * @return \AwardWallet\MainBundle\Entity\Account
     */
    public function getAccountid()
    {
        return $this->accountid;
    }
}
