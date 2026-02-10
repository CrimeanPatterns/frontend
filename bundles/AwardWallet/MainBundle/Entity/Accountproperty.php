<?php

namespace AwardWallet\MainBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * Accountproperty.
 *
 * @ORM\Table(name="AccountProperty")
 * @ORM\Entity(repositoryClass="AwardWallet\MainBundle\Entity\Repositories\AccountpropertyRepository")
 */
class Accountproperty
{
    /**
     * @var int
     * @ORM\Column(name="AccountPropertyID", type="integer", nullable=false)
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="IDENTITY")
     */
    protected $accountpropertyid;

    /**
     * @var string
     * @ORM\Column(name="Val", type="string", length=20000, nullable=true)
     */
    protected $val;

    /**
     * @var Subaccount
     * @ORM\ManyToOne(targetEntity="Subaccount")
     * @ORM\JoinColumns({
     *   @ORM\JoinColumn(name="SubAccountID", referencedColumnName="SubAccountID")
     * })
     */
    protected $subaccountid;

    /**
     * @var ProviderProperty
     * @ORM\ManyToOne(targetEntity="Providerproperty")
     * @ORM\JoinColumns({
     *   @ORM\JoinColumn(name="ProviderPropertyID", referencedColumnName="ProviderPropertyID")
     * })
     */
    protected $providerpropertyid;

    /**
     * @var Account
     * @ORM\ManyToOne(targetEntity="Account", inversedBy="Properties")
     * @ORM\JoinColumns({
     *   @ORM\JoinColumn(name="AccountID", referencedColumnName="AccountID")
     * })
     */
    protected $accountid;

    /**
     * Get accountpropertyid.
     *
     * @return int
     */
    public function getAccountpropertyid()
    {
        return $this->accountpropertyid;
    }

    /**
     * Set val.
     *
     * @param string $val
     * @return Accountproperty
     */
    public function setVal($val)
    {
        $this->val = $val;

        return $this;
    }

    /**
     * Get val.
     *
     * @return string
     */
    public function getVal()
    {
        return $this->val;
    }

    /**
     * Set subaccountid.
     *
     * @return Accountproperty
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

    /**
     * Set providerpropertyid.
     *
     * @return Accountproperty
     */
    public function setProviderpropertyid(?Providerproperty $providerpropertyid = null)
    {
        $this->providerpropertyid = $providerpropertyid;

        return $this;
    }

    /**
     * Get providerpropertyid.
     *
     * @return \AwardWallet\MainBundle\Entity\Providerproperty
     */
    public function getProviderpropertyid()
    {
        return $this->providerpropertyid;
    }

    /**
     * Set accountid.
     *
     * @return Accountproperty
     */
    public function setAccountid(?Account $accountid = null)
    {
        $this->accountid = $accountid;

        return $this;
    }

    /**
     * Get accountid.
     *
     * @return Account
     */
    public function getAccountid()
    {
        return $this->accountid;
    }
}
