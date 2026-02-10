<?php

namespace AwardWallet\MainBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * Bonusconversion.
 *
 * @ORM\Table(name="BonusConversion")
 * @ORM\Entity(repositoryClass="AwardWallet\MainBundle\Entity\Repositories\BonusConversionRepository")
 */
class BonusConversion
{
    /**
     * @var int
     * @ORM\Column(name="BonusConversionID", type="integer", nullable=false)
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="IDENTITY")
     */
    protected $id;

    /**
     * @var string
     * @ORM\Column(name="Airline", type="string", length=80, nullable=false)
     */
    protected $airline;

    /**
     * @var int
     * @ORM\Column(name="Points", type="integer", nullable=false)
     */
    protected $points;

    /**
     * @var int
     * @ORM\Column(name="Miles", type="integer", nullable=false)
     */
    protected $miles;

    /**
     * @var \DateTime
     * @ORM\Column(name="CreationDate", type="datetime", nullable=false)
     */
    protected $creationDate;

    /**
     * @var bool
     * @ORM\Column(name="Processed", type="boolean", nullable=false)
     */
    protected $processed;

    /**
     * @var float
     * @ORM\Column(name="Cost", type="float", nullable=true)
     */
    protected $cost;

    /**
     * @var Usr
     * @ORM\ManyToOne(targetEntity="Usr")
     * @ORM\JoinColumns({
     *   @ORM\JoinColumn(name="UserID", referencedColumnName="UserID")
     * })
     */
    protected $user;

    /**
     * @var Account
     * @ORM\ManyToOne(targetEntity="Account")
     * @ORM\JoinColumns({
     *   @ORM\JoinColumn(name="AccountID", referencedColumnName="AccountID")
     * })
     */
    protected $account;

    /**
     * @return int
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * @return string
     */
    public function getAirline()
    {
        return $this->airline;
    }

    /**
     * @param string $airline
     * @return BonusConversion
     */
    public function setAirline($airline)
    {
        $this->airline = $airline;

        return $this;
    }

    /**
     * @return int
     */
    public function getPoints()
    {
        return $this->points;
    }

    /**
     * @param int $points
     * @return BonusConversion
     */
    public function setPoints($points)
    {
        $this->points = $points;

        return $this;
    }

    /**
     * @return int
     */
    public function getMiles()
    {
        return $this->miles;
    }

    /**
     * @param int $miles
     * @return BonusConversion
     */
    public function setMiles($miles)
    {
        $this->miles = $miles;

        return $this;
    }

    /**
     * @return \DateTime
     */
    public function getCreationDate()
    {
        return $this->creationDate;
    }

    /**
     * @param \DateTime $creationDate
     * @return BonusConversion
     */
    public function setCreationDate($creationDate)
    {
        $this->creationDate = $creationDate;

        return $this;
    }

    /**
     * @return bool
     */
    public function isProcessed()
    {
        return $this->processed;
    }

    /**
     * @param bool $processed
     * @return BonusConversion
     */
    public function setProcessed($processed)
    {
        $this->processed = $processed;

        return $this;
    }

    /**
     * @return float
     */
    public function getCost()
    {
        return $this->cost;
    }

    /**
     * @param float $cost
     * @return BonusConversion
     */
    public function setCost($cost)
    {
        $this->cost = $cost;

        return $this;
    }

    /**
     * @return Usr
     */
    public function getUser()
    {
        return $this->user;
    }

    /**
     * @param Usr $user
     * @return BonusConversion
     */
    public function setUser($user)
    {
        $this->user = $user;

        return $this;
    }

    /**
     * @return Account
     */
    public function getAccount()
    {
        return $this->account;
    }

    /**
     * @param Account $account
     * @return BonusConversion
     */
    public function setAccount($account)
    {
        $this->account = $account;

        return $this;
    }
}
