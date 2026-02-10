<?php

namespace AwardWallet\MainBundle\Entity;

use AwardWallet\Common\DateTimeUtils;
use AwardWallet\Common\Entity\Geotag;
use AwardWallet\MainBundle\Loyalty\AccountSaving\Sources\SourceListInterface;
use AwardWallet\MainBundle\Timeline\Item;
use AwardWallet\MainBundle\Timeline\QueryOptions;
use AwardWallet\MainBundle\Timeline\SegmentSourceInterface;
use Doctrine\ORM\Mapping as ORM;
use JMS\TranslationBundle\Model\Message;
use JMS\TranslationBundle\Translation\TranslationContainerInterface;

/**
 * Rental.
 *
 * @ORM\Table(name="Rental")
 * @ORM\EntityListeners({ "AwardWallet\MainBundle\Entity\Listener\ItineraryListener" })
 * @ORM\Entity(repositoryClass="AwardWallet\MainBundle\Entity\Repositories\RentalRepository")
 * @ORM\AttributeOverrides({
 *      @ORM\AttributeOverride(name="id",
 *          column=@ORM\Column(name = "RentalID", type="integer", nullable=false)
 *      ),
 *      @ORM\AttributeOverride(name="confirmationNumber",
 *          column=@ORM\Column(name = "Number", type="string", length=100, nullable=false)
 *      ),
 *      @ORM\AttributeOverride(name="phone",
 *          column=@ORM\Column(name = "PickupPhone", type="string", length=20, nullable=true)
 *      ),
 * })
 */
class Rental extends Itinerary implements SegmentSourceInterface, TranslationContainerInterface, SourceListInterface, ShowAIWarningForEmailSourceInterface
{
    use SourceTrait;
    use AIWarningTrait;

    public const TYPE_RENTAL = 'rental';
    public const TYPE_TAXI = 'taxi_ride';

    public const SEGMENT_MAP_START = 'PU';
    public const SEGMENT_MAP_END = 'DO';

    /**
     * @var string
     * @ORM\Column(name="PickupLocation", type="string", length=160, nullable=false)
     */
    protected $pickuplocation;

    /**
     * @var string
     * @ORM\Column(name="PickupHours", type="string", length=4096, nullable=true)
     */
    protected $pickuphours;

    /**
     * @var \DateTime
     * @ORM\Column(name="PickupDatetime", type="datetime", nullable=false)
     */
    protected $pickupdatetime;

    /**
     * @var string
     * @ORM\Column(name="DropoffLocation", type="string", length=160, nullable=false)
     */
    protected $dropofflocation;

    /**
     * @var string
     * @ORM\Column(name="DropoffPhone", type="string", length=20, nullable=true)
     */
    protected $dropoffphone;

    /**
     * @var string
     * @ORM\Column(name="DropoffHours", type="string", length=4096, nullable=true)
     */
    protected $dropoffhours;

    /**
     * @var \DateTime
     * @ORM\Column(name="DropoffDatetime", type="datetime", nullable=false)
     */
    protected $dropoffdatetime;

    /**
     * @var string|null
     * @ORM\Column(name="RentalCompanyName", type="string", length=80, nullable=true)
     */
    protected $rentalCompanyName;

    /**
     * @var GeoTag
     * @ORM\ManyToOne(targetEntity="\AwardWallet\Common\Entity\Geotag")
     * @ORM\JoinColumns({
     *   @ORM\JoinColumn(name="DropoffGeoTagID", referencedColumnName="GeoTagID")
     * })
     */
    protected $dropoffgeotagid;

    /**
     * @var GeoTag
     * @ORM\ManyToOne(targetEntity="\AwardWallet\Common\Entity\Geotag")
     * @ORM\JoinColumns({
     *   @ORM\JoinColumn(name="PickupGeoTagID", referencedColumnName="GeoTagID")
     * })
     */
    protected $pickupgeotagid;

    /**
     * @var \DateTime
     * @ORM\Column(name="ChangeDate", type="datetime", nullable=true)
     */
    protected $changedate;

    /**
     * @var string
     * @ORM\Column(name="Type", type="string", nullable=false)
     */
    protected $type = 'rental';

    /**
     * @var string|null
     * @ORM\Column(type="string", name="CarImageUrl")
     */
    private $carImageUrl;

    /**
     * @var string|null
     * @ORM\Column(type="string", name="CarModel")
     */
    private $carModel;

    /**
     * @var string|null
     * @ORM\Column(type="string", name="CarType")
     */
    private $carType;

    /**
     * @var RentalDiscountDetails[]|null
     * @ORM\Column(name="DiscountDetails", type="jms_json", nullable=true)
     */
    private $discountDetails;

    /**
     * @var string|null
     * @ORM\Column(type="string", name="PickUpFax")
     */
    private $pickUpFax;

    /**
     * @var string|null
     * @ORM\Column(type="string", name="DropOffFax")
     */
    private $dropOffFax;

    /**
     * @var PricedEquipment[]|null
     * @ORM\Column(name="PricedEquipment", type="jms_json", nullable=true)
     */
    private $pricedEquipment;

    /**
     * Set pickuplocation.
     *
     * @param string $pickuplocation
     * @return Rental
     */
    public function setPickuplocation($pickuplocation)
    {
        $this->pickuplocation = $pickuplocation;

        return $this;
    }

    /**
     * Get pickuplocation.
     *
     * @return string
     */
    public function getPickuplocation()
    {
        if (null !== $this->pickupgeotagid) {
            return $this->pickupgeotagid->getAddress();
        }

        return $this->pickuplocation;
    }

    /**
     * Set pickupphone.
     *
     * @param string $pickupphone
     * @return Rental
     */
    public function setPickupphone($pickupphone)
    {
        $this->phone = $pickupphone;

        return $this;
    }

    /**
     * Get pickupphone.
     *
     * @return string
     */
    public function getPickupphone()
    {
        return $this->phone;
    }

    /**
     * Set pickuphours.
     *
     * @param string $pickuphours
     * @return Rental
     */
    public function setPickuphours($pickuphours)
    {
        $this->pickuphours = $pickuphours;

        return $this;
    }

    /**
     * Get pickuphours.
     *
     * @return string
     */
    public function getPickuphours()
    {
        return $this->pickuphours;
    }

    /**
     * Set pickupdatetime.
     *
     * @param \DateTime $pickupdatetime
     * @return Rental
     */
    public function setPickupdatetime($pickupdatetime)
    {
        $this->pickupdatetime = $pickupdatetime;

        return $this;
    }

    /**
     * Get pickupdatetime.
     *
     * @return \DateTime
     */
    public function getPickupdatetime()
    {
        return $this->pickupdatetime;
    }

    /**
     * Set dropofflocation.
     *
     * @param string $dropofflocation
     * @return Rental
     */
    public function setDropofflocation($dropofflocation)
    {
        $this->dropofflocation = $dropofflocation;

        return $this;
    }

    /**
     * Get dropofflocation.
     *
     * @return string
     */
    public function getDropofflocation()
    {
        if (null !== $this->dropoffgeotagid) {
            return $this->dropoffgeotagid->getAddress();
        }

        return $this->dropofflocation;
    }

    /**
     * Set dropoffphone.
     *
     * @param string $dropoffphone
     * @return Rental
     */
    public function setDropoffphone($dropoffphone)
    {
        $this->dropoffphone = $dropoffphone;

        return $this;
    }

    /**
     * Get dropoffphone.
     *
     * @return string
     */
    public function getDropoffphone()
    {
        return $this->dropoffphone;
    }

    /**
     * Set dropoffhours.
     *
     * @param string $dropoffhours
     * @return Rental
     */
    public function setDropoffhours($dropoffhours)
    {
        $this->dropoffhours = $dropoffhours;

        return $this;
    }

    /**
     * Get dropoffhours.
     *
     * @return string
     */
    public function getDropoffhours()
    {
        return $this->dropoffhours;
    }

    /**
     * Set dropoffdatetime.
     *
     * @param \DateTime $dropoffdatetime
     * @return Rental
     */
    public function setDropoffdatetime($dropoffdatetime)
    {
        $this->dropoffdatetime = $dropoffdatetime;

        return $this;
    }

    /**
     * Get dropoffdatetime.
     *
     * @return \DateTime
     */
    public function getDropoffdatetime()
    {
        return $this->dropoffdatetime;
    }

    public function setRentalCompanyName(?string $rentalCompanyName): void
    {
        $this->rentalCompanyName = $rentalCompanyName;
    }

    public function getRentalCompanyName(bool $useReferences = false): ?string
    {
        $result = $this->rentalCompanyName;

        if (empty($result) && $useReferences && $this->provider !== null) {
            $result = $this->provider->getShortname();
        }

        return $result;
    }

    /**
     * Set dropoffgeotagid.
     *
     * @return Rental
     */
    public function setDropoffgeotagid(?Geotag $dropoffgeotagid = null)
    {
        if (null !== $dropoffgeotagid) {
            $this->dropofflocation = $dropoffgeotagid->getAddress();
        } else {
            $this->dropofflocation = null;
        }
        $this->dropoffgeotagid = $dropoffgeotagid;

        return $this;
    }

    /**
     * Get dropoffgeotagid.
     *
     * @return Geotag
     */
    public function getDropoffgeotagid()
    {
        return $this->dropoffgeotagid;
    }

    /**
     * Set pickupgeotagid.
     *
     * @return Rental
     */
    public function setPickupgeotagid(?Geotag $pickupgeotagid = null)
    {
        if (null !== $pickupgeotagid) {
            $this->pickuplocation = $pickupgeotagid->getAddress();
        } else {
            $this->pickuplocation = null;
        }
        $this->pickupgeotagid = $pickupgeotagid;

        return $this;
    }

    /**
     * Get pickupgeotagid.
     *
     * @return Geotag
     */
    public function getPickupgeotagid()
    {
        return $this->pickupgeotagid;
    }

    /**
     * @return \DateTime
     */
    public function getChangedate()
    {
        return $this->changedate;
    }

    /**
     * @param \DateTime $changedate
     * @return Rental
     */
    public function setChangedate($changedate)
    {
        $this->changedate = $changedate;

        return $this;
    }

    public function getTimelineItems(Usr $user, ?QueryOptions $queryOptions = null): array
    {
        if (empty($this->pickupgeotagid)) {
            $tag = FindGeoTag($this->pickuplocation);
            $this->geotagid = $queryOptions->getGeotags()->find($tag['GeoTagID']);
        }

        if (empty($this->dropoffgeotagid)) {
            $tag = FindGeoTag($this->dropofflocation);
            $this->geotagid = $queryOptions->getGeotags()->find($tag['GeoTagID']);
        }

        switch ($this->type) {
            case self::TYPE_TAXI:
                return $this->getTaxiTimelineItems();

            default:
                return $this->getRentalTimelineItems();
        }
    }

    public function getKind(): string
    {
        return Itinerary::KIND_RENTAL;
    }

    public static function getSegmentMap()
    {
        return [self::SEGMENT_MAP_START, self::SEGMENT_MAP_END];
    }

    public function getDays()
    {
        return self::getDayCount($this->pickupdatetime, $this->dropoffdatetime);
    }

    public static function getDayCount(\DateTime $startDate, \DateTime $endDate): int
    {
        return max(
            (strtotime($endDate->format('Y-m-d')) - strtotime($startDate->format('Y-m-d'))) / DateTimeUtils::SECONDS_PER_DAY,
            1
        );
    }

    public function getPhones()
    {
        $phones = [];

        if (null !== ($phone = $this->getPickupphone())) {
            $phones[] = $phone;
        }

        if (null !== ($phone = $this->getDropoffphone())) {
            $phones[] = $phone;
        }

        return $phones;
    }

    /**
     * @return Geotag[]
     */
    public function getGeoTags()
    {
        $result = [];

        if (!empty($this->pickupgeotagid)) {
            $result[] = $this->pickupgeotagid;
        }

        if (!empty($this->dropoffgeotagid)) {
            $result[] = $this->dropoffgeotagid;
        }

        return $result;
    }

    public function getStartDate()
    {
        return $this->pickupdatetime;
    }

    public function getEndDate()
    {
        return $this->dropoffdatetime;
    }

    public function getUTCStartDate()
    {
        return Geotag::getLocalDateTimeByGeoTag($this->pickupdatetime, $this->pickupgeotagid);
    }

    public function getUTCEndDate()
    {
        return Geotag::getLocalDateTimeByGeoTag($this->dropoffdatetime, $this->dropoffgeotagid);
    }

    public function getType(): string
    {
        return $this->type;
    }

    public function setType(string $type)
    {
        $this->type = $type;
    }

    public function setRealProvider(?Provider $provider = null)
    {
        parent::setRealProvider($provider);

        if (null !== $provider) {
            $this->rentalCompanyName = $provider->getShortname();
        }
    }

    public function getCarImageUrl(): ?string
    {
        return $this->carImageUrl;
    }

    public function setCarImageUrl(?string $carImageUrl): void
    {
        $this->carImageUrl = $carImageUrl;
    }

    public function getCarModel(): ?string
    {
        return $this->carModel;
    }

    public function setCarModel(?string $carModel): void
    {
        $this->carModel = $carModel;
    }

    public function getCarType(): ?string
    {
        return $this->carType;
    }

    public function setCarType(?string $carType): void
    {
        $this->carType = $carType;
    }

    /**
     * @return RentalDiscountDetails[]|null
     */
    public function getDiscountDetails(): ?array
    {
        return $this->discountDetails;
    }

    /**
     * @param RentalDiscountDetails[]|null $discountDetails
     */
    public function setDiscountDetails(?array $discountDetails): self
    {
        $this->discountDetails = $discountDetails;

        return $this;
    }

    public function getPickUpFax(): ?string
    {
        return $this->pickUpFax;
    }

    public function setPickUpFax(?string $pickUpFax): void
    {
        $this->pickUpFax = $pickUpFax;
    }

    public function getDropOffFax(): ?string
    {
        return $this->dropOffFax;
    }

    public function setDropOffFax(?string $dropOffFax): void
    {
        $this->dropOffFax = $dropOffFax;
    }

    /**
     * @return PricedEquipment[]|null
     */
    public function getPricedEquipment(): ?array
    {
        return $this->pricedEquipment;
    }

    /**
     * @param PricedEquipment[]|null $pricedEquipment
     */
    public function setPricedEquipment(?array $pricedEquipment): self
    {
        $this->pricedEquipment = $pricedEquipment;

        return $this;
    }

    public static function getTranslationMessages()
    {
        return [
            (new Message(
                'rental.dates-inconsistent',
                'validators'
            ))->setDesc('Dropoff date cannot precede the pickup date.'),
        ];
    }

    private function getRentalTimelineItems()
    {
        $pickup = new Item\Pickup($this);
        $dropOff = new Item\Dropoff($this);

        $pickup->setConnection($dropOff);
        $dropOff->setConnection($pickup);

        return [$pickup, $dropOff];
    }

    private function getTaxiTimelineItems()
    {
        return [new Item\Taxi($this)];
    }
}
