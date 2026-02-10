<?php

namespace AwardWallet\MainBundle\Entity;

use AwardWallet\Common\Entity\Geotag;
use AwardWallet\MainBundle\Globals\StringHandler;
use AwardWallet\MainBundle\Timeline\SegmentSourceInterface;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\Common\Collections\Criteria;
use Doctrine\ORM\Mapping as ORM;
use JMS\TranslationBundle\Translation\TranslationContainerInterface;
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;

/**
 * @ORM\MappedSuperclass
 * @UniqueEntity(
 *     fields={"account", "user", "userAgent", "confirmationNumber"}
 * )
 */
abstract class Itinerary implements GeotagInterface, DateRangeInterface, TranslationContainerInterface, ContainsConfirmationNumbers, Cancellable, SegmentSourceInterface
{
    use OwnableTrait;

    public const KIND_TRIP = 'T';
    public const KIND_RESERVATION = 'R';
    public const KIND_RENTAL = 'L';
    public const KIND_RESTAURANT = 'E';
    public const KIND_PARKING = 'P';

    public const ITINERARY_KIND_TABLE = [
        self::KIND_TRIP => 1,
        self::KIND_RESERVATION => 2,
        self::KIND_RENTAL => 3,
        self::KIND_RESTAURANT => 4,
        self::KIND_PARKING => 5,
    ];

    public static $table = [
        self::KIND_TRIP => 'Trip',
        self::KIND_RESERVATION => 'Reservation',
        self::KIND_RENTAL => 'Rental',
        self::KIND_RESTAURANT => 'Restaurant',
        self::KIND_PARKING => 'Parking',
    ];

    /**
     * @var int
     * @ORM\Column(name="ID", type="integer", nullable=false)
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="IDENTITY")
     */
    protected $id;

    /**
     * @var Provider
     * @ORM\ManyToOne(targetEntity="Provider")
     * @ORM\JoinColumns({
     *   @ORM\JoinColumn(name="ProviderID", referencedColumnName="ProviderID")
     * })
     */
    protected $provider;

    /**
     * @var Provider
     * @ORM\ManyToOne(targetEntity="Provider")
     * @ORM\JoinColumns({
     *   @ORM\JoinColumn(name="TravelAgencyID", referencedColumnName="ProviderID")
     * })
     */
    protected $travelAgency;

    /**
     * @var Provider
     * @ORM\ManyToOne(targetEntity="Provider")
     * @ORM\JoinColumns({
     *   @ORM\JoinColumn(name="SpentAwardsProviderID", referencedColumnName="ProviderID")
     * })
     */
    protected $spentAwardsProviderID;

    /**
     * @var string
     * @ORM\Column(name="ConfFields", type="string", length=250, nullable=true)
     */
    protected $confFields;

    /**
     * @var Account
     * @ORM\ManyToOne(targetEntity="Account")
     * @ORM\JoinColumns({
     *   @ORM\JoinColumn(name="AccountID", referencedColumnName="AccountID")
     * })
     */
    protected $account;

    /**
     * @var string
     * @ORM\Column(name="ShareCode", type="string", length=20)
     */
    protected $shareCode;

    /**
     * @var bool
     * @ORM\Column(name="Hidden", type="boolean", nullable=false)
     */
    protected $hidden = false;

    /**
     * @var bool
     * @ORM\Column(name="Undeleted", type="boolean", nullable=false)
     */
    protected $undeleted = false;

    /**
     * @var string
     * @ORM\Column(name="Notes", type="string", length=4000, nullable=true)
     */
    protected $notes;

    /**
     * @var string
     * @ORM\Column(name="Comment", type="string", nullable=true)
     */
    protected $comment;

    /**
     * @var bool
     * @ORM\Column(name="Moved", type="boolean", nullable=false)
     */
    protected $moved = false;

    /**
     * @var \DateTime
     * @ORM\Column(name="CreateDate", type="datetime", nullable=false)
     */
    protected $createDate;

    /**
     * @var \DateTime
     * @ORM\Column(type="datetime", nullable=false)
     */
    protected $firstSeenDate;

    /**
     * @var \DateTime
     * @ORM\Column(name="UpdateDate", type="datetime", nullable=true)
     */
    protected $updateDate;

    /**
     * @var \DateTime
     * @ORM\Column(name="MailDate", type="datetime", nullable=true)
     */
    protected $mailDate;

    /**
     * TODO possibly obsolete - consider removing.
     *
     * @var string
     * @ORM\Column(name="Hash", type="string", length=64, nullable=true)
     */
    protected $hash;

    /**
     * @var bool
     * @ORM\Column(name="Parsed", type="boolean", nullable=false)
     */
    protected $parsed = false;

    /**
     * @var bool
     * @ORM\Column(name="Copied", type="boolean", nullable=false)
     */
    protected $copied = false;

    /**
     * @var bool
     * @ORM\Column(name="Modified", type="boolean", nullable=false)
     */
    protected $modified = false;

    /**
     * @var bool
     * @ORM\Column(name="Cancelled", type="boolean", nullable=false)
     */
    protected $cancelled = false;

    /**
     * @var int
     * @ORM\Column(name="PlanIndex", type="integer", nullable=false)
     */
    protected $planindex = 0;

    /**
     * @var Travelplan
     * @ORM\ManyToOne(targetEntity="Travelplan")
     * @ORM\JoinColumns({
     *   @ORM\JoinColumn(name="TravelPlanID", referencedColumnName="TravelPlanID")
     * })
     */
    protected $travelPlan;

    /**
     * @var string
     * @ORM\Column(name="confirmationNumber", type="string", length=100, nullable=false)
     */
    protected $confirmationNumber;

    /**
     * @var string[]
     * @ORM\Column(name="ConfirmationNumbers", type="simple_array")
     */
    protected $providerConfirmationNumbers = [];

    /**
     * Travel agency confirmation numbers.
     *
     * @var string[]
     * @ORM\Column(name="TravelAgencyConfirmationNumbers", type="simple_array")
     */
    protected $travelAgencyConfirmationNumbers = [];

    /**
     * @var string|null
     * @ORM\Column(name="Phone", type="string", length=80)
     */
    protected $phone;

    /**
     * @var \DateTime
     * @ORM\Column(name="LastParseDate", type="datetime", nullable=true)
     */
    protected $lastParseDate;

    /**
     * @var Files\ItineraryFile[]|Collection
     * @ORM\OneToMany(targetEntity="AwardWallet\MainBundle\Entity\Files\ItineraryFile", mappedBy="itineraryId", orphanRemoval=false, fetch="EXTRA_LAZY")
     */
    protected $files;

    /**
     * @var string[]
     * @ORM\Column(name="ParsedAccountNumbers", type="simple_array")
     */
    private $parsedAccountNumbers = [];

    /**
     * @var string[]
     * @ORM\Column(name="TravelAgencyParsedAccountNumbers", type="simple_array")
     */
    private $travelAgencyParsedAccountNumbers = [];

    /**
     * @var string[]
     * @ORM\Column(name="TravelAgencyPhones", type="simple_array")
     */
    private $travelAgencyPhones = [];

    /**
     * @var PricingInfo|null
     * @ORM\Embedded(class="PricingInfo", columnPrefix=false)
     */
    private $pricingInfo;

    /**
     * When the reservation was actually made.
     *
     * @var \DateTime|null
     * @ORM\Column(name="ReservationDate", type="datetime")
     */
    private $reservationDate;

    /**
     * @var string[]
     * @ORM\Column(name="TravelerNames", type="simple_array")
     */
    private $travelerNames = [];

    /**
     * @var string|null
     * @ORM\Column(type="string", name="CancellationPolicy")
     */
    private $cancellationPolicy;

    /**
     * @var string|null
     * @ORM\Column(name="ParsedStatus", type="string", length=20)
     */
    private $parsedStatus;

    public function __construct()
    {
        $this->createDate = new \DateTime();
        $this->firstSeenDate = new \DateTime();
        $this->shareCode = StringHandler::getRandomCode(20);
        $this->pricingInfo = new PricingInfo(null, null, null, null, null, null, null, null);
        $this->files = new ArrayCollection();
    }

    public function __clone()
    {
        $this->id = null;
    }

    public function getIdString()
    {
        return $this->getKind() . '.' . (string) $this->getId();
    }

    /**
     * @return self
     */
    public function setRealProvider(?Provider $provider = null)
    {
        $this->provider = $provider;

        return $this;
    }

    /**
     * Get travel agency provider or real provider if known.
     */
    public function getProvider(): ?Provider
    {
        return $this->travelAgency ?? $this->provider;
    }

    public function getRealProvider(): ?Provider
    {
        return $this->provider;
    }

    public function getTravelAgency(): ?Provider
    {
        return $this->travelAgency;
    }

    public function setTravelAgency(?Provider $travelAgency)
    {
        $this->travelAgency = $travelAgency;
    }

    public function getSpentAwardsProvider(): ?Provider
    {
        return $this->spentAwardsProviderID;
    }

    public function setSpentAwardsProvider(?Provider $spentAwardsProviderID): self
    {
        $this->spentAwardsProviderID = $spentAwardsProviderID;

        return $this;
    }

    /**
     * something like ['RecordLocator' => 'XXXYY', 'LastName' => 'Smith']
     * array of fields, if this reservation was retrieved by confirmation number.
     *
     * @return array|null
     */
    public function getConfFields()
    {
        if (empty($this->confFields)) {
            return null;
        }

        return unserialize($this->confFields, ['allowed_classes' => false]);
    }

    public function setConfFields(array $fields)
    {
        $this->confFields = serialize($fields);

        return $this;
    }

    /**
     * @return self
     */
    public function setAccount(?Account $account = null)
    {
        $this->account = $account;

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
     * @return self
     */
    public function setUser(?Usr $user = null)
    {
        $this->user = $user;

        return $this;
    }

    /**
     * @return Usr
     */
    public function getUser()
    {
        return $this->user;
    }

    public function canAutologinWithConfNo(?Usr $user = null)
    {
        return
            (
                !empty($this->provider)
                && $this->provider->getState() !== PROVIDER_DISABLED
                && in_array(
                    $this->provider->getUserItineraryAutoLogin($user),
                    [ITINERARY_AUTOLOGIN_ACCOUNT, ITINERARY_AUTOLOGIN_BOTH]
                )
            )
            || (
                !empty($this->confFields)
                && !empty($this->provider)
                && $this->provider->getState() !== PROVIDER_DISABLED
                && in_array(
                    $this->provider->getUserItineraryAutoLogin($user),
                    [ITINERARY_AUTOLOGIN_CONFNO, ITINERARY_AUTOLOGIN_BOTH]
                )
            );
    }

    public function canAutologinWithAccount(?Usr $user = null)
    {
        return !empty($this->account)
            && !empty($this->provider)
            && $this->provider->getState() !== PROVIDER_DISABLED
            && in_array(
                $this->provider->getUserItineraryAutoLogin($user),
                [ITINERARY_AUTOLOGIN_ACCOUNT, ITINERARY_AUTOLOGIN_BOTH]
            );
    }

    public function getId()
    {
        return $this->id;
    }

    abstract public function getPhones();

    /**
     * Get shareCode.
     *
     * @return string
     */
    public function getShareCode()
    {
        return $this->shareCode;
    }

    /**
     * @return string
     */
    public function getEncodedShareCode()
    {
        return base64_encode($this->getKind() . '.' . $this->id . '.' . $this->shareCode);
    }

    /**
     * @return $this
     */
    public function setUserAgent(?Useragent $userAgent = null)
    {
        $this->userAgent = $userAgent;

        return $this;
    }

    /**
     * @return Useragent
     */
    public function getUserAgent()
    {
        return $this->userAgent;
    }

    /**
     * @param bool $hidden
     * @return $this
     */
    public function setHidden($hidden)
    {
        $this->hidden = $hidden;

        return $this;
    }

    /**
     * @return bool
     */
    public function getHidden()
    {
        return $this->hidden;
    }

    public function isUndeleted(): bool
    {
        return $this->undeleted;
    }

    public function setUndeleted(bool $undeleted): self
    {
        $this->undeleted = $undeleted;

        return $this;
    }

    public function canRefreshByConfNo(?Usr $user = null)
    {
        if (empty($this->confFields)) {
            return false;
        }
        $mainProvider = $this->getProvider();

        if (null === $mainProvider) {
            return false;
        }

        return $mainProvider->canCheckConfirmation($user);
    }

    /**
     * Set parsed.
     *
     * @param bool $parsed
     * @return $this
     */
    public function setParsed($parsed)
    {
        $this->parsed = $parsed;

        return $this;
    }

    /**
     * Get parsed.
     *
     * @return bool
     */
    public function getParsed()
    {
        return $this->parsed;
    }

    /**
     * Set notes.
     *
     * @param string $notes
     * @return $this
     */
    public function setNotes($notes)
    {
        $this->notes = $notes;

        return $this;
    }

    /**
     * Get notes.
     *
     * @return string
     */
    public function getNotes()
    {
        return $this->notes;
    }

    public function getComment(): ?string
    {
        return $this->comment;
    }

    public function setComment(?string $comment): self
    {
        $this->comment = $comment;

        return $this;
    }

    /**
     * Set moved.
     *
     * @param bool $moved
     * @return $this
     */
    public function setMoved($moved)
    {
        $this->moved = $moved;

        return $this;
    }

    /**
     * Get moved.
     *
     * @return bool
     */
    public function getMoved()
    {
        return $this->moved;
    }

    /**
     * Set updatedate.
     *
     * @return $this
     */
    public function setUpdateDate(\DateTime $updatedate)
    {
        $this->updateDate = $updatedate;

        return $this;
    }

    /**
     * Get updatedate.
     *
     * @return \DateTime
     */
    public function getUpdateDate()
    {
        return $this->updateDate;
    }

    /**
     * Get createdate.
     *
     * @return \DateTime
     */
    public function getCreateDate()
    {
        return $this->createDate;
    }

    public function getFirstSeenDate(): \DateTimeInterface
    {
        return $this->firstSeenDate;
    }

    public function setFirstSeenDate(\DateTimeInterface $dateTime): self
    {
        $this->firstSeenDate = $dateTime;

        return $this;
    }

    /**
     * Set createdate.
     *
     * @return $this
     */
    public function setCreateDate(\DateTime $createdate)
    {
        $this->createDate = $createdate;

        return $this;
    }

    /**
     * Set cancelled.
     *
     * @param bool $cancelled
     * @return $this
     */
    public function setCancelled($cancelled)
    {
        $this->cancelled = $cancelled;

        return $this;
    }

    /**
     * Get cancelled.
     *
     * @return bool
     */
    public function getCancelled()
    {
        return $this->cancelled;
    }

    /**
     * Set hash.
     *
     * @param string $hash
     * @return $this
     */
    public function setHash($hash)
    {
        $this->hash = $hash;

        return $this;
    }

    /**
     * Get hash.
     *
     * @return string
     */
    public function getHash()
    {
        return $this->hash;
    }

    /**
     * Set copied.
     *
     * @param bool $copied
     * @return $this
     */
    public function setCopied($copied)
    {
        $this->copied = $copied;

        return $this;
    }

    /**
     * Get copied.
     *
     * @return bool
     */
    public function getCopied()
    {
        return $this->copied;
    }

    /**
     * Set modified.
     *
     * @param bool $modified
     * @return $this
     */
    public function setModified($modified)
    {
        $this->modified = $modified;

        return $this;
    }

    /**
     * Get modified.
     *
     * @return bool
     */
    public function getModified()
    {
        return $this->modified;
    }

    /**
     * Set planindex.
     *
     * @param int $planindex
     * @return $this
     */
    public function setPlanindex($planindex)
    {
        $this->planindex = $planindex;

        return $this;
    }

    /**
     * Get planindex.
     *
     * @return int
     */
    public function getPlanindex()
    {
        return $this->planindex;
    }

    /**
     * Set maildate.
     *
     * @return $this
     */
    public function setMailDate(?\DateTime $maildate = null)
    {
        $this->mailDate = $maildate;

        return $this;
    }

    /**
     * Get maildate.
     *
     * @return \DateTime
     */
    public function getMailDate()
    {
        return $this->mailDate;
    }

    /**
     * Set travelplanid.
     *
     * @return $this
     */
    public function setTravelPlan(?Travelplan $travelplanid = null)
    {
        $this->travelPlan = $travelplanid;

        return $this;
    }

    /**
     * Get travelplanid.
     *
     * @return \AwardWallet\MainBundle\Entity\Travelplan
     */
    public function getTravelPlan()
    {
        return $this->travelPlan;
    }

    public function setConfirmationNumber(?string $confirmationNumber): void
    {
        $this->confirmationNumber = $confirmationNumber;
    }

    public function getConfirmationNumber(bool $fallbackToTravelAgencyNumber = false): ?string
    {
        if (!$fallbackToTravelAgencyNumber) {
            return $this->confirmationNumber;
        }

        if ($this->confirmationNumber !== null) {
            return $this->confirmationNumber;
        }

        if (count($this->travelAgencyConfirmationNumbers) > 0) {
            return $this->travelAgencyConfirmationNumbers[0];
        }

        return null;
    }

    public function setProviderConfirmationNumbers(array $providerConfirmationNumbers): void
    {
        $this->providerConfirmationNumbers = $providerConfirmationNumbers;
    }

    /**
     * @return string[]
     */
    public function getProviderConfirmationNumbers(): array
    {
        return $this->providerConfirmationNumbers;
    }

    /**
     * @param string[] $numbers
     */
    public function setTravelAgencyConfirmationNumbers(array $numbers)
    {
        $this->travelAgencyConfirmationNumbers = $numbers;
    }

    /**
     * @return string[]
     */
    public function getTravelAgencyConfirmationNumbers(): array
    {
        return $this->travelAgencyConfirmationNumbers;
    }

    /**
     * @return Geotag[]
     */
    abstract public function getGeoTags();

    /**
     * @return Usr
     */
    public function getUserid()
    {
        return $this->getUser();
    }

    /**
     * @return self
     */
    public function setUserid(?Usr $user = null)
    {
        return $this->setUser($user);
    }

    /**
     * @return Useragent
     */
    public function getUseragentid()
    {
        return $this->getUserAgent();
    }

    /**
     * @return Itinerary
     */
    public function setUseragentid(?Useragent $useragent = null)
    {
        return $this->setUserAgent($useragent);
    }

    /**
     * Returns an array of messages.
     *
     * @return array<Message>
     */
    public static function getTranslationMessages()
    {
        return [];
    }

    /**
     * @return string|null
     */
    public function getPhone()
    {
        return $this->phone;
    }

    /**
     * @return $this
     */
    public function setPhone(?string $phone = null)
    {
        $this->phone = $phone;

        return $this;
    }

    /**
     * Set LastParseDate.
     *
     * @return $this
     */
    public function setLastParseDate(?\DateTime $parsedDate = null)
    {
        $this->lastParseDate = $parsedDate;

        return $this;
    }

    /**
     * Get LastParseDate.
     *
     * @return \DateTime|null
     */
    public function getLastParseDate()
    {
        return $this->lastParseDate;
    }

    /**
     * @deprecated use getTravelerNames() instead
     * @return string
     */
    public function getNames()
    {
        return implode(', ', $this->travelerNames);
    }

    /**
     * @return string[]
     */
    public function getParsedAccountNumbers(): array
    {
        return $this->parsedAccountNumbers;
    }

    /**
     * @param string[] $parsedAccountNumbers
     */
    public function setParsedAccountNumbers(array $parsedAccountNumbers): void
    {
        $this->parsedAccountNumbers = $parsedAccountNumbers;
    }

    /**
     * @return string[]
     */
    public function getTravelAgencyParsedAccountNumbers(): array
    {
        return $this->travelAgencyParsedAccountNumbers;
    }

    /**
     * @param string[] $travelAgencyParsedAccountNumbers
     */
    public function setTravelAgencyParsedAccountNumbers(array $travelAgencyParsedAccountNumbers): void
    {
        $this->travelAgencyParsedAccountNumbers = $travelAgencyParsedAccountNumbers;
    }

    public function getTravelAgencyPhones(): array
    {
        return $this->travelAgencyPhones;
    }

    /**
     * @param string[] $travelAgencyPhones
     */
    public function setTravelAgencyPhones(array $travelAgencyPhones): void
    {
        $this->travelAgencyPhones = $travelAgencyPhones;
    }

    public function getPricingInfo(): PricingInfo
    {
        return $this->pricingInfo;
    }

    public function setPricingInfo(PricingInfo $pricingInfo): void
    {
        $this->pricingInfo = $pricingInfo;
    }

    public function getReservationDate(): ?\DateTime
    {
        return $this->reservationDate;
    }

    public function setReservationDate(?\DateTime $reservationDate): void
    {
        $this->reservationDate = $reservationDate;
    }

    /**
     * @return string[]
     */
    public function getTravelerNames(): array
    {
        return $this->travelerNames;
    }

    /**
     * @param string[] $travelerNames
     */
    public function setTravelerNames(array $travelerNames): void
    {
        $this->travelerNames = $travelerNames;
    }

    public function getCancellationPolicy(): ?string
    {
        return $this->cancellationPolicy;
    }

    public function setCancellationPolicy(?string $cancellationPolicy): void
    {
        $this->cancellationPolicy = $cancellationPolicy;
    }

    public function cancel(): void
    {
        $this->cancelled = true;
        $this->hidden = true;
        $this->undeleted = false;
    }

    public function getAllConfirmationNumbers(): array
    {
        // array_filter without callback remove nulls
        return array_filter(array_merge(
            $this->providerConfirmationNumbers,
            $this->travelAgencyConfirmationNumbers
        ));
    }

    public function getParsedStatus(): ?string
    {
        return $this->parsedStatus;
    }

    public function setParsedStatus(?string $parsedStatus): void
    {
        $this->parsedStatus = $parsedStatus;
    }

    /**
     * @return string flight, reservation, rental, etc
     */
    abstract public function getType(): string;

    /**
     * @return string T,R,L,E,P
     */
    abstract public function getKind(): string;

    public function isHiddenByUser(): bool
    {
        return false;
    }

    public function getFiles()
    {
        if (null === $this->id || !array_key_exists($this->getKind(), self::ITINERARY_KIND_TABLE)) {
            return new ArrayCollection([]);
        }

        $criteria = Criteria::create();
        $criteria
            ->where(Criteria::expr()->eq('itineraryTable', self::ITINERARY_KIND_TABLE[$this->getKind()]))
            ->andWhere(Criteria::expr()->eq('itineraryId', $this->id));

        return $this->files->matching($criteria);
    }

    public function setFiles(array $files): self
    {
        $this->files = new ArrayCollection($files);

        return $this;
    }

    public function addFile(Files\ItineraryFile $file): self
    {
        $this->files[] = $file;

        return $this;
    }

    public static function getItineraryClass(string $kindOrTable): string
    {
        switch ($kindOrTable) {
            case self::KIND_TRIP:
            case self::$table[self::KIND_TRIP]:
                return Trip::class;

            case 'TripSegment':
            case 'Tripsegment':
                return Tripsegment::class;

            case self::KIND_RESERVATION:
            case self::$table[self::KIND_RESERVATION]:
                return Reservation::class;

            case self::KIND_RENTAL:
            case self::$table[self::KIND_RENTAL]:
                return Rental::class;

            case self::KIND_RESTAURANT:
            case self::$table[self::KIND_RESTAURANT]:
                return Restaurant::class;

            case self::KIND_PARKING:
            case self::$table[self::KIND_PARKING]:
                return Parking::class;
        }

        throw new \InvalidArgumentException(sprintf('Unknown itinerary kind or table: %s', $kindOrTable));
    }
}
