<?php

namespace AwardWallet\MainBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * AwardWallet\MainBundle\Entity\AAMembership.
 *
 * @ORM\Entity
 * @ORM\Table(name="AAMembership")
 */
class AAMembership
{
    /**
     * @var int
     * @ORM\Column(type="integer", nullable=false)
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="IDENTITY")
     */
    protected $AAMembershipID;

    /**
     * @var string
     * @ORM\Column(type="string", length=30, nullable=true)
     */
    protected $FirstName;

    /**
     * @var string
     * @ORM\Column(type="string", length=30, nullable=true)
     */
    protected $LastName;

    /**
     * @var int
     * @ORM\Column(type="integer", nullable=true)
     */
    protected $Visits;

    /**
     * @var float
     * @ORM\Column(type="decimal", nullable=true)
     */
    protected $Balance;

    /**
     * @var \DateTime
     * @ORM\Column(type="datetime", nullable=true)
     */
    protected $Expiration;

    /**
     * @var string
     * @ORM\Column(type="string", length=80, nullable=true)
     */
    protected $Account;

    /**
     * @var string
     * @ORM\Column(type="string", length=40, nullable=true)
     */
    protected $Status;

    /**
     * @var int
     * @ORM\Column(type="integer", nullable=true)
     */
    protected $Tier1;

    /**
     * @var int
     * @ORM\Column(type="integer", nullable=true)
     */
    protected $Tier2;

    /**
     * @var int
     * @ORM\Column(type="integer", nullable=true)
     */
    protected $Tier3;

    /**
     * @var \DateTime
     * @ORM\Column(type="datetime", nullable=true)
     */
    protected $SnapDate;

    /**
     * @var int
     * @ORM\Column(type="integer", nullable=true)
     */
    protected $UserID;

    /**
     * @var int
     * @ORM\Column(type="integer", nullable=true)
     */
    protected $AccountID;

    /**
     * @var int
     * @ORM\Column(type="integer", nullable=true)
     */
    protected $ProviderID;

    /**
     * Get AAMembershipID.
     *
     * @return int
     */
    public function getAAMembershipID()
    {
        return $this->AAMembershipID;
    }

    /**
     * Set FirstName.
     *
     * @param string $FirstName
     * @return AAMembership
     */
    public function setFirstName($FirstName)
    {
        $this->FirstName = $FirstName;

        return $this;
    }

    /**
     * Get FirstName.
     *
     * @return string
     */
    public function getFirstName()
    {
        return $this->FirstName;
    }

    /**
     * Set LastName.
     *
     * @param string $LastName
     * @return AAMembership
     */
    public function setLastName($LastName)
    {
        $this->LastName = $LastName;

        return $this;
    }

    /**
     * Get LastName.
     *
     * @return string
     */
    public function getLastName()
    {
        return $this->LastName;
    }

    /**
     * Set Visits.
     *
     * @param int $Visits
     * @return AAMembership
     */
    public function setVisits($Visits)
    {
        $this->Visits = $Visits;

        return $this;
    }

    /**
     * Get Visits.
     *
     * @return int
     */
    public function getVisits()
    {
        return $this->Visits;
    }

    /**
     * Set Balance.
     *
     * @param float $Balance
     * @return AAMembership
     */
    public function setBalance($Balance)
    {
        $this->Balance = $Balance;

        return $this;
    }

    /**
     * Get Balance.
     *
     * @return float
     */
    public function getBalance()
    {
        return $this->Balance;
    }

    /**
     * Set Expiration.
     *
     * @param \DateTime $Expiration
     * @return AAMembership
     */
    public function setExpiration($Expiration)
    {
        $this->Expiration = $Expiration;

        return $this;
    }

    /**
     * Get Expiration.
     *
     * @return \DateTime
     */
    public function getExpiration()
    {
        return $this->Expiration;
    }

    /**
     * Set Account.
     *
     * @param string $Account
     * @return AAMembership
     */
    public function setAccount($Account)
    {
        $this->Account = $Account;

        return $this;
    }

    /**
     * Get Account.
     *
     * @return string
     */
    public function getAccount()
    {
        return $this->Account;
    }

    /**
     * Set Status.
     *
     * @param string $Status
     * @return AAMembership
     */
    public function setStatus($Status)
    {
        $this->Status = $Status;

        return $this;
    }

    /**
     * Get Status.
     *
     * @return string
     */
    public function getStatus()
    {
        return $this->Status;
    }

    /**
     * Set Tier1.
     *
     * @param int $Tier1
     * @return AAMembership
     */
    public function setTier1($Tier1)
    {
        $this->Tier1 = $Tier1;

        return $this;
    }

    /**
     * Get Tier1.
     *
     * @return int
     */
    public function getTier1()
    {
        return $this->Tier1;
    }

    /**
     * Set Tier2.
     *
     * @param int $Tier2
     * @return AAMembership
     */
    public function setTier2($Tier2)
    {
        $this->Tier2 = $Tier2;

        return $this;
    }

    /**
     * Get Tier2.
     *
     * @return int
     */
    public function getTier2()
    {
        return $this->Tier2;
    }

    /**
     * Set Tier3.
     *
     * @param int $Tier3
     * @return AAMembership
     */
    public function setTier3($Tier3)
    {
        $this->Tier3 = $Tier3;

        return $this;
    }

    /**
     * Get Tier3.
     *
     * @return int
     */
    public function getTier3()
    {
        return $this->Tier3;
    }

    /**
     * Set SnapDate.
     *
     * @param \DateTime $SnapDate
     * @return AAMembership
     */
    public function setSnapDate($SnapDate)
    {
        $this->SnapDate = $SnapDate;

        return $this;
    }

    /**
     * Get SnapDate.
     *
     * @return \DateTime
     */
    public function getSnapDate()
    {
        return $this->SnapDate;
    }

    /**
     * @param int $UserID
     */
    public function setUserID($UserID)
    {
        $this->UserID = $UserID;
    }

    /**
     * @return int
     */
    public function getUserID()
    {
        return $this->UserID;
    }

    /**
     * @param int $AccountID
     */
    public function setAccountID($AccountID)
    {
        $this->AccountID = $AccountID;
    }

    /**
     * @return int
     */
    public function getAccountID()
    {
        return $this->AccountID;
    }

    /**
     * @param int $ProviderID
     */
    public function setProviderID($ProviderID)
    {
        $this->ProviderID = $ProviderID;
    }

    /**
     * @return int
     */
    public function getProviderID()
    {
        return $this->ProviderID;
    }
}
