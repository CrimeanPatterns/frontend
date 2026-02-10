<?php

namespace AwardWallet\MainBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * Accountbalance.
 *
 * @ORM\Table(name="AccountBalance")
 * @ORM\Entity
 */
class Accountbalance
{
    /**
     * @var int
     * @ORM\Column(name="AccountBalanceID", type="integer", nullable=false)
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="IDENTITY")
     */
    protected $accountbalanceid;

    /**
     * @var \DateTime
     * @ORM\Column(name="UpdateDate", type="datetime", nullable=false)
     */
    protected $updatedate;

    /**
     * @var float
     * @ORM\Column(name="Balance", type="float", nullable=false)
     */
    protected $balance;

    /**
     * @var \Account
     * @ORM\ManyToOne(targetEntity="Account", inversedBy="balanceHistory")
     * @ORM\JoinColumns({
     *   @ORM\JoinColumn(name="AccountID", referencedColumnName="AccountID")
     * })
     */
    protected $accountid;

    /**
     * @var \Subaccount
     * @ORM\ManyToOne(targetEntity="Subaccount")
     * @ORM\JoinColumns({
     *   @ORM\JoinColumn(name="SubAccountID", referencedColumnName="SubAccountID")
     * })
     */
    protected $subaccountid;

    /**
     * Get accountbalanceid.
     *
     * @return int
     */
    public function getAccountbalanceid()
    {
        return $this->accountbalanceid;
    }

    /**
     * Set updatedate.
     *
     * @param \DateTime $updatedate
     * @return Accountbalance
     */
    public function setUpdatedate($updatedate)
    {
        $this->updatedate = $updatedate;

        return $this;
    }

    /**
     * Get updatedate.
     *
     * @return \DateTime
     */
    public function getUpdatedate()
    {
        return $this->updatedate;
    }

    /**
     * Set balance.
     *
     * @param float $balance
     * @return Accountbalance
     */
    public function setBalance($balance)
    {
        $this->balance = $balance;

        return $this;
    }

    /**
     * Get balance.
     *
     * @return float
     */
    public function getBalance()
    {
        return $this->balance;
    }

    /**
     * Set accountid.
     *
     * @return Accountbalance
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

    /**
     * Set subaccountid.
     *
     * @return Accountbalance
     */
    public function setSubaccountid(?Subaccount $subaccountid = null)
    {
        $this->subaccountid = $subaccountid;

        return $this;
    }

    /**
     * Get subaccountid.
     *
     * @return \AwardWallet\MainBundle\Entity\Subaccount
     */
    public function getSubaccountid()
    {
        return $this->subaccountid;
    }
}
