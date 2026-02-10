<?php

namespace AwardWallet\MainBundle\Entity;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Doctrine\ORM\PersistentCollection;

/**
 * Subaccount.
 *
 * @ORM\Table(name="SubAccount")
 * @ORM\Entity(repositoryClass="AwardWallet\MainBundle\Entity\Repositories\SubaccountRepository")
 */
class Subaccount implements LoyaltyProgramInterface
{
    use CardImageContainerTrait;
    use LocationContainerTrait;
    use CustomLoyaltyPropertyContainerTrait;

    /**
     * @var int
     * @ORM\Column(name="SubAccountID", type="integer", nullable=false)
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="IDENTITY")
     */
    protected $subaccountid;

    /**
     * @var string
     * @ORM\Column(name="DisplayName", type="string", length=255, nullable=true)
     */
    protected $displayname;

    /**
     * @var float
     * @ORM\Column(name="Balance", type="float", nullable=true)
     */
    protected $balance;

    /**
     * @var float
     * @ORM\Column(name="LastBalance", type="float", nullable=true)
     */
    protected $lastbalance;

    /**
     * @var int
     * @ORM\Column(name="ChangeCount", type="integer", nullable=false)
     */
    protected $changecount = 0;

    /**
     * @var \DateTime
     * @ORM\Column(name="ExpirationDate", type="date", nullable=true)
     */
    protected $expirationdate;

    /**
     * @var \DateTime
     * @ORM\Column(name="LastChangeDate", type="datetime", nullable=true)
     */
    protected $lastchangedate;

    /**
     * @var bool
     * @ORM\Column(name="ExpirationAutoSet", type="boolean", nullable=true)
     */
    protected $expirationautoset;

    /**
     * @var string
     * @ORM\Column(name="ExpirationWarning", type="text", nullable=true)
     */
    protected $expirationwarning;

    /**
     * @var string
     * @ORM\Column(name="Code", type="string", length=250, nullable=false)
     */
    protected $code;

    /**
     * @var string
     * @ORM\Column(name="Kind", type="string", length=1, nullable=true)
     */
    protected $kind;

    /**
     * @var CreditCard
     * @ORM\ManyToOne(targetEntity="CreditCard")
     * @ORM\JoinColumns({
     *   @ORM\JoinColumn(name="CreditCardID", referencedColumnName="CreditCardID")
     * })
     */
    protected $creditcard;

    /**
     * @var Account
     * @ORM\ManyToOne(targetEntity="Account")
     * @ORM\JoinColumns({
     *   @ORM\JoinColumn(name="AccountID", referencedColumnName="AccountID")
     * })
     */
    protected $accountid;

    /**
     * @var CardImage[]|Collection
     * @ORM\OneToMany(
     *     targetEntity="CardImage",
     *     mappedBy="subaccountid",
     *     cascade={"persist", "remove"},
     *     orphanRemoval=false,
     *     indexBy="kind"
     * )
     */
    protected $cardImages;

    /**
     * @var Location[]|Collection
     * @ORM\OneToMany(
     *     targetEntity="Location",
     *     mappedBy="subaccount",
     *     cascade={"persist", "remove"},
     *     orphanRemoval=true,
     *     indexBy="name"
     * )
     */
    protected $locations;

    /**
     * @var CustomLoyaltyProperty[]|PersistentCollection
     * @ORM\OneToMany(
     *     targetEntity="CustomLoyaltyProperty",
     *     mappedBy="subaccountid",
     *     cascade={"persist", "remove"},
     *     orphanRemoval=true,
     *     indexBy="name"
     * )
     */
    protected $customLoyaltyProperties;

    /**
     * @var bool
     * @ORM\Column(name="IsHidden", type="boolean", nullable=false)
     */
    protected $ishidden;

    /**
     * @var AccountProperty[]|Collection
     * @ORM\OneToMany(targetEntity="Accountproperty", mappedBy="subaccountid", cascade={"persist", "remove", "refresh"}, orphanRemoval=true)
     */
    protected $Properties;

    /**
     * Subaccount constructor.
     */
    public function __construct()
    {
        $this->cardImages = new ArrayCollection();
        $this->locations = new ArrayCollection();
        $this->customLoyaltyProperties = new ArrayCollection();
        $this->Properties = new ArrayCollection();
    }

    public function __toString()
    {
        return $this->subaccountid . "." . $this->accountid->getAccountid() . "." . $this->code;
    }

    public function getId()
    {
        return $this->subaccountid;
    }

    public function getUserid()
    {
        return $this->accountid->getUserid();
    }

    public function setUserid(?Usr $user = null)
    {
        return $this->accountid->setUserid($user);
    }

    public function getUseragentid()
    {
        return $this->accountid->getUseragentid();
    }

    public function setUseragentid(?Useragent $useragent = null)
    {
        return $this->accountid->setUseragentid($useragent);
    }

    /**
     * Get subaccountid.
     *
     * @return int
     */
    public function getSubaccountid()
    {
        return $this->subaccountid;
    }

    /**
     * Set displayname.
     *
     * @param string $displayname
     * @return Subaccount
     */
    public function setDisplayname($displayname)
    {
        $this->displayname = $displayname;

        return $this;
    }

    /**
     * Get displayname.
     *
     * @return string
     */
    public function getDisplayname()
    {
        return $this->displayname;
    }

    /**
     * Set balance.
     *
     * @param float $balance
     * @return Subaccount
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
     * Set lastbalance.
     *
     * @param float $lastbalance
     * @return Subaccount
     */
    public function setLastbalance($lastbalance)
    {
        $this->lastbalance = $lastbalance;

        return $this;
    }

    /**
     * Get lastbalance.
     *
     * @return float
     */
    public function getLastbalance()
    {
        return $this->lastbalance;
    }

    /**
     * Set changecount.
     *
     * @param int $changecount
     * @return Subaccount
     */
    public function setChangecount($changecount)
    {
        $this->changecount = $changecount;

        return $this;
    }

    /**
     * Get changecount.
     *
     * @return int
     */
    public function getChangecount()
    {
        return $this->changecount;
    }

    /**
     * Set expirationdate.
     *
     * @param \DateTime $expirationdate
     * @return Subaccount
     */
    public function setExpirationdate($expirationdate)
    {
        $this->expirationdate = $expirationdate;

        return $this;
    }

    /**
     * Get expirationdate.
     *
     * @return \DateTime
     */
    public function getExpirationdate()
    {
        return $this->expirationdate;
    }

    /**
     * Set lastchangedate.
     *
     * @param \DateTime $lastchangedate
     * @return Subaccount
     */
    public function setLastchangedate($lastchangedate)
    {
        $this->lastchangedate = $lastchangedate;

        return $this;
    }

    /**
     * Get lastchangedate.
     *
     * @return \DateTime
     */
    public function getLastchangedate()
    {
        return $this->lastchangedate;
    }

    /**
     * Set expirationautoset.
     *
     * @param bool $expirationautoset
     * @return Subaccount
     */
    public function setExpirationautoset($expirationautoset)
    {
        $this->expirationautoset = $expirationautoset;

        return $this;
    }

    /**
     * Get expirationautoset.
     *
     * @return bool
     */
    public function getExpirationautoset()
    {
        return $this->expirationautoset;
    }

    /**
     * Set expirationwarning.
     *
     * @param string $expirationwarning
     * @return Subaccount
     */
    public function setExpirationwarning($expirationwarning)
    {
        $this->expirationwarning = $expirationwarning;

        return $this;
    }

    /**
     * Get expirationwarning.
     *
     * @return string
     */
    public function getExpirationwarning()
    {
        return $this->expirationwarning;
    }

    /**
     * Set code.
     *
     * @param string $code
     * @return Subaccount
     */
    public function setCode($code)
    {
        $this->code = $code;

        return $this;
    }

    /**
     * Get code.
     *
     * @return string
     */
    public function getCode()
    {
        return $this->code;
    }

    /**
     * Set kind.
     *
     * @param string $kind
     * @return Subaccount
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
     * @return Subaccount
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
     * Set IsHidden.
     *
     * @param bool $option
     * @return Subaccount
     */
    public function setIsHidden($option)
    {
        $this->ishidden = $option;

        return $this;
    }

    /**
     * Get IsHidden.
     *
     * @return bool
     */
    public function getIsHidden()
    {
        return $this->ishidden;
    }

    /**
     * @return AccountProperty[]|Collection
     */
    public function getProperties()
    {
        return $this->Properties;
    }

    /**
     * @param AccountProperty[]|Collection $properties
     */
    public function setProperties($properties): self
    {
        $this->Properties = $properties;

        return $this;
    }

    public function getPropertyByCode(string $code): ?string
    {
        $properties = $this->getProperties();

        if (!$properties || !$properties->count()) {
            return null;
        }

        $filteredProperties = $properties->filter(fn (Accountproperty $p) => $p->getProviderpropertyid() ? $p->getProviderpropertyid()->getCode() === $code : false);

        if ($filteredProperties->count() > 0) {
            $filteredProperties = $filteredProperties->first();

            /** @var Accountproperty $filteredProperties */
            return $filteredProperties->getVal();
        }

        return null;
    }

    public function getCreditcard(): ?CreditCard
    {
        return $this->creditcard;
    }

    public function setCreditcard(?CreditCard $creditcard)
    {
        $this->creditcard = $creditcard;

        return $this;
    }

    public function getCreditCardFormattedDisplayName()
    {
        if ($this->creditcard instanceof CreditCard && $this->creditcard->getDisplayNameFormat()) {
            return CreditCard::formatCreditCardName(
                $this->displayname, $this->creditcard->getDisplayNameFormat(), $this->accountid->getProviderid()->getProviderid()
            );
        }

        return $this->displayname;
    }
}
