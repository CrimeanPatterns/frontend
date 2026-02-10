<?php

namespace AwardWallet\MainBundle\Entity;

use AwardWallet\MainBundle\Validator\Constraints as AwAssert;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Doctrine\ORM\PersistentCollection;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * Providercoupon.
 *
 * @ORM\Table(name="ProviderCoupon")
 * @ORM\Entity(repositoryClass="AwardWallet\MainBundle\Repository\ProvidercouponRepository")
 * @ORM\EntityListeners({ "AwardWallet\MainBundle\Entity\Listener\ProvidercouponListener" })
 * @AwAssert\Account
 */
class Providercoupon implements LoyaltyProgramInterface
{
    use CardImageContainerTrait;
    use LocationContainerTrait;
    use CustomLoyaltyPropertyContainerTrait;
    use OwnableTrait {
        setOwner as protected traitSetOwner;
    }

    public const TYPE_GIFT_CARD = 1;
    public const TYPE_TRAVEL_VOUCHER = 2;
    public const TYPE_CERTIFICATE = 3;
    public const TYPE_TICKET = 4;
    public const TYPE_TICKET_COMPANION = 5;
    public const TYPE_COUPON = 6;
    public const TYPE_STORE_CREDIT = 7;
    public const TYPE_PASSPORT = 8;
    public const TYPE_TRUSTED_TRAVELER = 9;
    public const TYPE_VACCINE_CARD = 10;
    public const TYPE_INSURANCE_CARD = 11;
    public const TYPE_VISA = 12;
    public const TYPE_DRIVERS_LICENSE = 13;
    public const TYPE_PRIORITY_PASS = 14;

    public const TYPES = [
        self::TYPE_CERTIFICATE => 'Certificate',
        self::TYPE_TICKET_COMPANION => 'Companion Ticket',
        self::TYPE_COUPON => 'Coupon',
        self::TYPE_GIFT_CARD => 'Gift Card',
        self::TYPE_STORE_CREDIT => 'Store Credit',
        self::TYPE_TICKET => 'Ticket',
        self::TYPE_TRAVEL_VOUCHER => 'Travel Voucher',
        /*
        self::TYPE_INSURANCE_CARD => 'Insurance Card',
        self::TYPE_VISA => 'Visa',
        self::TYPE_DRIVERS_LICENSE => 'Drivers License',
        self::TYPE_PRIORITY_PASS => 'Priority Pass',
        */
    ];

    public const DOCUMENT_TYPES = [
        self::TYPE_PASSPORT => 'Passport',
        self::TYPE_TRUSTED_TRAVELER => 'Trusted Traveler Number',
        self::TYPE_VACCINE_CARD => 'Vaccine Card',
        self::TYPE_INSURANCE_CARD => 'Insurance Card',
        self::TYPE_VISA => 'Visa',
        self::TYPE_DRIVERS_LICENSE => 'Drivers License',
        self::TYPE_PRIORITY_PASS => 'Priority Pass',
    ];

    public const DOCUMENT_TYPE_TO_KEY_MAP = [
        self::TYPE_PASSPORT => self::KEY_TYPE_PASSPORT,
        self::TYPE_TRUSTED_TRAVELER => self::KEY_TYPE_TRAVELER_NUMBER,
        self::TYPE_VACCINE_CARD => self::KEY_TYPE_VACCINE_CARD,
        self::TYPE_INSURANCE_CARD => self::KEY_TYPE_INSURANCE_CARD,
        self::TYPE_VISA => self::KEY_TYPE_VISA,
        self::TYPE_DRIVERS_LICENSE => self::KEY_TYPE_DRIVERS_LICENSE,
        self::TYPE_PRIORITY_PASS => self::KEY_TYPE_PRIORITY_PASS,
    ];

    public const DOCUMENT_KEY_TO_TYPE_MAP = [
        self::KEY_TYPE_PASSPORT => self::TYPE_PASSPORT,
        self::KEY_TYPE_TRAVELER_NUMBER => self::TYPE_TRUSTED_TRAVELER,
        self::KEY_TYPE_VACCINE_CARD => self::TYPE_VACCINE_CARD,
        self::KEY_TYPE_INSURANCE_CARD => self::TYPE_INSURANCE_CARD,
        self::KEY_TYPE_VISA => self::TYPE_VISA,
        self::KEY_TYPE_DRIVERS_LICENSE => self::TYPE_DRIVERS_LICENSE,
        self::KEY_TYPE_PRIORITY_PASS => self::TYPE_PRIORITY_PASS,
    ];

    public const DOCUMENT_TYPE_TO_FIELD_MAP = [
        self::TYPE_PASSPORT => self::FIELD_KEY_PASSPORT,
        self::TYPE_TRUSTED_TRAVELER => self::FIELD_KEY_TRUSTED_TRAVELER,
        self::TYPE_VACCINE_CARD => self::FIELD_KEY_VACCINE_CARD,
        self::TYPE_INSURANCE_CARD => self::FIELD_KEY_INSURANCE_CARD,
        self::TYPE_VISA => self::FIELD_KEY_VISA,
        self::TYPE_DRIVERS_LICENSE => self::FIELD_KEY_DRIVERS_LICENSE,
        self::TYPE_PRIORITY_PASS => self::FIELD_KEY_PRIORITY_PASS,
    ];

    public const KEY_TYPE_PASSPORT = 'passport';
    public const KEY_TYPE_TRAVELER_NUMBER = 'traveler-number';
    public const KEY_TYPE_VACCINE_CARD = 'vaccine-card';
    public const KEY_TYPE_INSURANCE_CARD = 'insurance-card';
    public const KEY_TYPE_VISA = 'visa';
    public const KEY_TYPE_DRIVERS_LICENSE = 'drivers-license';
    public const KEY_TYPE_PRIORITY_PASS = 'priority-pass';

    public const FIELD_KEY_VACCINE_CARD = 'vaccineCard';
    public const FIELD_KEY_INSURANCE_CARD = 'insuranceCard';
    public const FIELD_KEY_VISA = 'visa';
    public const FIELD_KEY_PASSPORT = 'passport';
    public const FIELD_KEY_DRIVERS_LICENSE = 'driversLicense';
    public const FIELD_KEY_TRUSTED_TRAVELER = 'trustedTraveler';
    public const FIELD_KEY_PRIORITY_PASS = 'priorityPass';

    public const NOT_ARCHIVED = 0;
    public const ARCHIVED = 1;

    public const INSURANCE_TYPE_LIST = [
        1 => 'Medical',
        2 => 'Dental',
        3 => 'Vision',
        50 => 'Other',
    ];
    public const INSURANCE_TYPE2_LIST = [
        1 => 'PPO',
        2 => 'HMO',
        3 => 'EPO',
        4 => 'POS',
    ];
    public const INSURANCE_POLICY_HOLDER_LIST = [
        1 => 'Self',
        2 => 'Spouse',
        3 => 'Child',
        4 => 'Mother',
        5 => 'Father',
        6 => 'Stepson or Stepdaughter',
        7 => 'Grandfather or Grandmother',
        8 => 'Grandson or Granddaughter',
        9 => 'Niece or Nephew',
        10 => 'Significant Other',
        11 => 'Foster Child',
        12 => 'Dependent of a Minor Dependent',
        13 => 'Ward, Emancipated Minor',
        50 => 'Other Relationship',
    ];

    /**
     * @var int
     * @ORM\Column(name="ProviderCouponID", type="integer", nullable=false)
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="IDENTITY")
     */
    protected $providercouponid;

    /**
     * @var string
     * @ORM\Column(name="Description", type="text", nullable=true)
     */
    protected $description;

    /**
     * @var string
     * @ORM\Column(name="Value", type="string", length=128, nullable=true)
     */
    protected $value;

    /**
     * @var \DateTime
     * @ORM\Column(name="ExpirationDate", type="datetime", nullable=true)
     */
    protected $expirationdate;

    /**
     * @var \DateTime
     * @ORM\Column(name="CreationDate", type="datetime", nullable=false)
     */
    protected $creationdate;

    /**
     * @var string
     * @ORM\Column(name="ProgramName", type="string", length=80, nullable=false)
     * @Assert\NotBlank()
     */
    protected $programname;

    /**
     * @var bool
     * @ORM\Column(name="Kind", type="integer", nullable=false)
     * @Assert\NotBlank()
     */
    protected $kind;

    /**
     * @var int
     * @ORM\Column(name="TypeID", type="integer", nullable=false)
     */
    protected $typeid;

    /**
     * @ORM\Column(name="TypeName", type="string", length=64, nullable=true)
     * @Assert\NotBlank()
     */
    protected ?string $typeName;

    /**
     * @var Account|null
     * @ORM\ManyToOne(targetEntity="Account")
     * @ORM\JoinColumn(name="AccountID", referencedColumnName="AccountID")
     */
    protected $account;

    /**
     * @var string
     * @ORM\Column(name="CardNumber", type="string", nullable=true)
     */
    protected $cardnumber;

    /**
     * @var int
     * @ORM\Column(name="Pin", type="string", nullable=true)
     */
    protected $pin;

    /**
     * @var int
     * @ORM\Column(name="DontTrackExpiration", type="boolean", nullable=false)
     */
    protected $donttrackexpiration = false;

    /**
     * @var Useragent[]|Collection
     * @ORM\ManyToMany(targetEntity="Useragent")
     * @ORM\JoinTable(name="ProviderCouponShare",
     *      joinColumns={@ORM\JoinColumn(name="ProviderCouponID", referencedColumnName="ProviderCouponID", onDelete="CASCADE")},
     *      inverseJoinColumns={@ORM\JoinColumn(name="UserAgentID", referencedColumnName="UserAgentID", unique=true)}
     * )
     */
    protected $useragents;

    /**
     * @var CardImage[]|Collection
     * @ORM\OneToMany(
     *     targetEntity="CardImage",
     *     mappedBy="providercouponid",
     *     cascade={"persist", "remove"},
     *     orphanRemoval=false,
     *     indexBy="kind"
     * )
     */
    protected $cardImages;

    /**
     * @var DocumentImage[]|Collection
     * @ORM\OneToMany(
     *     targetEntity="DocumentImage",
     *     mappedBy="providercouponid",
     *     cascade={"persist", "remove"},
     *     orphanRemoval=false
     * )
     */
    protected $documentImages;

    /**
     * @var Location[]|Collection
     * @ORM\OneToMany(
     *     targetEntity="Location",
     *     mappedBy="providercoupon",
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
     *     mappedBy="providercouponid",
     *     cascade={"persist", "remove"},
     *     orphanRemoval=true,
     *     indexBy="name"
     * )
     */
    protected $customLoyaltyProperties;

    /**
     * @var Currency
     * @ORM\ManyToOne(targetEntity="AwardWallet\MainBundle\Entity\Currency")
     * @ORM\JoinColumn(name="CurrencyID", referencedColumnName="CurrencyID", nullable=true)
     */
    protected $currency;

    /**
     * @var bool
     * @ORM\Column(name="IsArchived", type="boolean", nullable=false)
     */
    protected $isarchived = false;

    /**
     * @var array
     * @ORM\Column(name="CustomFields", type="json_array")
     */
    private $customFields;

    public function __construct()
    {
        $this->useragents = new ArrayCollection();
        $this->cardImages = new ArrayCollection();
        $this->documentImages = new ArrayCollection();
        $this->locations = new ArrayCollection();
        $this->customLoyaltyProperties = new ArrayCollection();
        $this->setCreationdate(new \DateTime());
    }

    /**
     * Get providercouponid.
     *
     * @return int
     */
    public function getProvidercouponid()
    {
        return $this->providercouponid;
    }

    public function getId()
    {
        return $this->getProvidercouponid();
    }

    /**
     * Set description.
     *
     * @param string $description
     * @return Providercoupon
     */
    public function setDescription($description)
    {
        $this->description = null === $description ? null : htmlspecialchars($description);

        return $this;
    }

    /**
     * Get description.
     *
     * @return string
     */
    public function getDescription()
    {
        return null === $this->description ? null : htmlspecialchars_decode($this->description);
    }

    /**
     * Set value.
     *
     * @param string $value
     * @return Providercoupon
     */
    public function setValue($value)
    {
        $this->value = null === $value ? null : htmlspecialchars($value);

        return $this;
    }

    /**
     * Get value.
     *
     * @return string
     */
    public function getValue()
    {
        return null === $this->value ? null : htmlspecialchars_decode($this->value);
    }

    /**
     * Set expirationdate.
     *
     * @param \DateTime $expirationdate
     * @return Providercoupon
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
     * Set creationdate.
     *
     * @param \DateTime $creationdate
     * @return Providercoupon
     */
    public function setCreationdate($creationdate)
    {
        $this->creationdate = $creationdate;

        return $this;
    }

    /**
     * Get creationdate.
     *
     * @return \DateTime
     */
    public function getCreationdate()
    {
        return $this->creationdate;
    }

    /**
     * Set programname.
     *
     * @param string $programname
     * @return Providercoupon
     */
    public function setProgramname($programname)
    {
        $this->programname = null === $programname ? null : htmlspecialchars($programname);

        return $this;
    }

    /**
     * Get programname.
     *
     * @return string
     */
    public function getProgramname()
    {
        return null === $this->programname ? null : htmlspecialchars_decode($this->programname);
    }

    /**
     * Set kind.
     *
     * @param int $kind
     * @return Providercoupon
     */
    public function setKind($kind)
    {
        $this->kind = $kind;

        return $this;
    }

    /**
     * Get kind.
     *
     * @return int
     */
    public function getKind()
    {
        return $this->kind;
    }

    /**
     * Set useragentid.
     *
     * @return Account
     * @deprecated use setUserAgent instead
     */
    public function setUseragentid(?Useragent $useragentid = null)
    {
        $this->setUserAgent($useragentid);
    }

    /**
     * @return $this
     */
    public function setUserAgent(?Useragent $useragentid): self
    {
        $this->userAgent = $useragentid;

        return $this;
    }

    /**
     * Get useragentid.
     *
     * @return \AwardWallet\MainBundle\Entity\Useragent
     */
    public function getUseragentid()
    {
        return $this->userAgent;
    }

    /**
     * Set userid.
     *
     * @return Account
     * @deprecated use setUser() instead
     */
    public function setUserid(?Usr $userid = null)
    {
        return $this->setUser($userid);
    }

    /**
     * @return $this
     */
    public function setUser(?Usr $user)
    {
        if (!empty($this->user) && (empty($user) || $this->user->getUserid() != $user->getUserid())) {
            $this->useragents = new ArrayCollection();
        }
        $this->user = $user;

        return $this;
    }

    public function setOwner(?Owner $owner)
    {
        if (null !== $this->user && $this->user !== $owner->getUser()) {
            $this->useragents = new ArrayCollection();
        }
        $this->traitSetOwner($owner);
    }

    /**
     * Get userid.
     *
     * @deprecated use getUser() instead
     * @return \AwardWallet\MainBundle\Entity\Usr
     */
    public function getUserid()
    {
        return $this->user;
    }

    public function getUser(): ?Usr
    {
        return $this->user;
    }

    /**
     * users with whom you shared this coupon.
     *
     * @return Useragent[]|ArrayCollection|Collection
     */
    public function getUseragents()
    {
        return $this->useragents;
    }

    public function getUseragentByUser(Usr $user)
    {
        $users = $this->getUseragents();

        return $users->filter(function (Useragent $ua) use ($user) {
            return $ua->getAgentid()->getUserid() == $user->getUserid() && $ua->getIsapproved();
        });
    }

    public function setUseragents($useragents)
    {
        if (is_array($useragents)) {
            $useragents = new ArrayCollection($useragents);
        }
        $this->useragents = $useragents;

        return $this;
    }

    /**
     * @return Providercoupon
     */
    public function addUserAgent(Useragent $useragent)
    {
        if (!$this->useragents->contains($useragent)) {
            $this->useragents->add($useragent);
        }

        return $this;
    }

    public function getOwnerFullName()
    {
        if ($this->getUseragentid()) {
            return $this->getUseragentid()->getFullName();
        } else {
            return $this->getUserid()->getFullName();
        }
    }

    /**
     * Set typeid.
     *
     * @return Providercoupon
     */
    public function setTypeid($typeid)
    {
        $this->typeid = $typeid;

        return $this;
    }

    /**
     * Get typeid.
     *
     * @return int
     */
    public function getTypeid()
    {
        return $this->typeid;
    }

    public function setTypeName(?string $typeName): self
    {
        $this->typeName = $typeName;

        if (empty($this->typeid)) {
            foreach ([self::TYPES, self::DOCUMENT_TYPES] as $types) {
                if (false === $key = array_search($typeName, $types)) {
                    continue;
                }

                $this->typeid = $key;
            }
        }

        return $this;
    }

    /**
     * Get coupon type.
     */
    public function getTypeName(): ?string
    {
        if (!empty($this->typeName)) {
            return $this->typeName;
        }

        $typeId = $this->getTypeid();

        return array_key_exists($typeId, self::TYPES) ? self::TYPES[$typeId] : '';
    }

    /**
     * Get document type.
     *
     * @return string
     */
    public function getDocumentTypeName()
    {
        $typeId = $this->getTypeid();

        return array_key_exists($typeId, self::DOCUMENT_TYPES) ? self::DOCUMENT_TYPES[$typeId] : '';
    }

    /**
     * Get coupon types.
     *
     * @return array
     */
    public function getTypes()
    {
        return self::TYPES;
    }

    /**
     * Set cardnumber.
     *
     * @return Providercoupon
     */
    public function setCardNumber($cardnumber)
    {
        $this->cardnumber = $cardnumber;

        return $this;
    }

    /**
     * Get cardnumber.
     *
     * @return string
     */
    public function getCardNumber()
    {
        return $this->cardnumber;
    }

    /**
     * Set pin.
     *
     * @return Providercoupon
     */
    public function setPin($pin)
    {
        $this->pin = preg_replace('/[^a-zA-Z0-9]/', '', $pin);

        return $this;
    }

    /**
     * Get pin.
     *
     * @return string
     */
    public function getPin()
    {
        return $this->pin;
    }

    /**
     * Set donttrackexpiration.
     *
     * @param bool $donttrackexpiration
     * @return Providercoupon
     */
    public function setDonttrackexpiration($donttrackexpiration)
    {
        $this->donttrackexpiration = $donttrackexpiration;

        return $this;
    }

    /**
     * Get donttrackexpiration.
     *
     * @return bool
     */
    public function getDonttrackexpiration()
    {
        return $this->donttrackexpiration;
    }

    public function getAccount(): ?Account
    {
        return $this->account;
    }

    public function setAccount(?Account $account): Providercoupon
    {
        $this->account = $account;

        return $this;
    }

    public function getCustomFields(): array
    {
        return $this->customFields ?: [];
    }

    public function setCustomFields(array $customFields): void
    {
        $this->customFields = $customFields;
    }

    public function isDocument(): bool
    {
        return $this->getKind() === PROVIDER_KIND_DOCUMENT;
    }

    /**
     * @return DocumentImage[]|PersistentCollection
     */
    public function getDocumentImages()
    {
        return $this->documentImages;
    }

    /**
     * @param DocumentImage[]|PersistentCollection $documentImages
     * @return $this
     */
    public function setDocumentImages($documentImages)
    {
        $this->documentImages = $documentImages;

        return $this;
    }

    /**
     * @return $this
     */
    public function addDocumentImage(DocumentImage $documentImage)
    {
        $this->documentImages[] = $documentImage;

        return $this;
    }

    /**
     * @return $this
     */
    public function removeDocumentImage(DocumentImage $documentImage)
    {
        if (is_array($this->documentImages)) {
            foreach ($this->documentImages as $key => $iterCardImage) {
                if ($iterCardImage === $documentImage) {
                    unset($this->documentImages[$key]);

                    break;
                }
            }
        } elseif ($this->documentImages instanceof Collection) {
            $this->documentImages->removeElement($documentImage);
        } else {
            throw new \RuntimeException('DocumentImages are uninitialized');
        }

        return $this;
    }

    public function getCurrency(): ?Currency
    {
        return $this->currency;
    }

    public function setCurrency(?Currency $currency): self
    {
        $this->currency = $currency;

        return $this;
    }

    public function getIsArchived(): bool
    {
        return $this->isarchived;
    }

    public function setIsArchived(bool $isarchived): self
    {
        $this->isarchived = $isarchived;

        return $this;
    }
}
