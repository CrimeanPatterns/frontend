<?php

namespace AwardWallet\MainBundle\Entity;

use AwardWallet\Common\Entity\Geotag;
use AwardWallet\MainBundle\Loyalty\AccountSaving\Sources\SourceListInterface;
use AwardWallet\MainBundle\Timeline\Item\Event;
use AwardWallet\MainBundle\Timeline\QueryOptions;
use AwardWallet\MainBundle\Timeline\SegmentSourceInterface;
use Doctrine\ORM\Mapping as ORM;
use JMS\TranslationBundle\Model\Message;
use JMS\TranslationBundle\Translation\TranslationContainerInterface;

/**
 * Restaurant.
 *
 * @ORM\Table(name="Restaurant")
 * @ORM\EntityListeners({ "AwardWallet\MainBundle\Entity\Listener\ItineraryListener" })
 * @ORM\Entity(repositoryClass="AwardWallet\MainBundle\Entity\Repositories\RestaurantRepository")
 * @ORM\AttributeOverrides({
 *      @ORM\AttributeOverride(name="id",
 *          column=@ORM\Column(name = "RestaurantID", type="integer", nullable=false)
 *      ),
 *      @ORM\AttributeOverride(name="confirmationNumber",
 *          column=@ORM\Column(name = "ConfNo", type="string", length=100, nullable=false)
 *      ),
 * })
 */
class Restaurant extends Itinerary implements SegmentSourceInterface, TranslationContainerInterface, SourceListInterface, ShowAIWarningForEmailSourceInterface
{
    use SourceTrait;
    use AIWarningTrait;

    public const EVENT_RESTAURANT = 1;
    public const EVENT_MEETING = 2;
    public const EVENT_SHOW = 3;
    public const EVENT_EVENT = 4;
    public const EVENT_CONFERENCE = 5;
    public const EVENT_RAVE = 6;

    public const EVENT_TYPES = [
        self::EVENT_RESTAURANT,
        self::EVENT_MEETING,
        self::EVENT_SHOW,
        self::EVENT_EVENT,
        self::EVENT_CONFERENCE,
        self::EVENT_RAVE,
    ];

    public const EVENT_TYPE_NAMES = [
        self::EVENT_RESTAURANT => 'Restaurant',
        self::EVENT_MEETING => 'Meeting',
        self::EVENT_SHOW => 'Show',
        self::EVENT_EVENT => 'Event',
        self::EVENT_CONFERENCE => 'Conference',
        self::EVENT_RAVE => 'Rave',
    ];

    public const SEGMENT_MAP = 'E';

    /**
     * @var string
     * @ORM\Column(name="Name", type="string", length=80, nullable=false)
     */
    protected $name;

    /**
     * @var string
     * @ORM\Column(name="Address", type="string", length=160, nullable=true)
     */
    protected $address;

    /**
     * @var string
     * @ORM\Column(name="Phone", type="string", length=80, nullable=true)
     */
    protected $phone;

    /**
     * @var string|null
     * @ORM\Column(name="Fax", type="string", length=80, nullable=true)
     */
    protected $fax;

    /**
     * @var int
     * @ORM\Column(name="EventType", type="integer", nullable=false)
     */
    protected $eventtype = 1;

    /**
     * @var \DateTime
     * @ORM\Column(name="StartDate", type="datetime", nullable=false)
     */
    protected $startdate;

    /**
     * @var \DateTime
     * @ORM\Column(name="EndDate", type="datetime", nullable=true)
     */
    protected $enddate;

    /**
     * @var Geotag
     * @ORM\ManyToOne(targetEntity="\AwardWallet\Common\Entity\Geotag")
     * @ORM\JoinColumns({
     *   @ORM\JoinColumn(name="GeoTagID", referencedColumnName="GeoTagID")
     * })
     */
    protected $geotagid;

    /**
     * @var \DateTime
     * @ORM\Column(name="ChangeDate", type="datetime", nullable=true)
     */
    protected $changedate;

    /**
     * @var int|null
     * @ORM\Column(type="integer", name="GuestCount")
     */
    protected $guestCount;

    /**
     * @var string[]|null
     * @ORM\Column(name="Seats", type="json", nullable=true)
     */
    protected $seats;

    /**
     * Set name.
     *
     * @param string $name
     * @return Restaurant
     */
    public function setName($name)
    {
        $this->name = $name;

        return $this;
    }

    /**
     * Get name.
     *
     * @return string
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * Set address.
     *
     * @param string $address
     * @return Restaurant
     */
    public function setAddress($address)
    {
        $this->address = $address;

        return $this;
    }

    /**
     * Get address.
     *
     * @return string
     */
    public function getAddress()
    {
        return $this->address;
    }

    /**
     * Set eventtype.
     *
     * @param int $eventtype
     * @return Restaurant
     */
    public function setEventtype($eventtype)
    {
        $this->eventtype = $eventtype;

        return $this;
    }

    /**
     * Get eventtype.
     *
     * @return int
     */
    public function getEventtype()
    {
        return $this->eventtype;
    }

    /**
     * Set startdate.
     *
     * @param \DateTime $startdate
     * @return Restaurant
     */
    public function setStartdate($startdate)
    {
        $this->startdate = $startdate;

        return $this;
    }

    /**
     * Get startdate.
     *
     * @return \DateTime
     */
    public function getStartdate()
    {
        return $this->startdate;
    }

    /**
     * Set enddate.
     *
     * @param \DateTime|null $enddate
     * @return Restaurant
     */
    public function setEnddate($enddate)
    {
        $this->enddate = $enddate;

        return $this;
    }

    /**
     * Get enddate.
     *
     * @return \DateTime
     */
    public function getEnddate()
    {
        return $this->enddate;
    }

    /**
     * Set geotagid.
     *
     * @return Restaurant
     */
    public function setGeotagid(?Geotag $geotagid = null)
    {
        $this->geotagid = $geotagid;

        return $this;
    }

    /**
     * Get geotagid.
     *
     * @return Geotag
     */
    public function getGeotagid()
    {
        return $this->geotagid;
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
     * @return Restaurant
     */
    public function setChangedate($changedate)
    {
        $this->changedate = $changedate;

        return $this;
    }

    public function getTimelineItems(Usr $user, ?QueryOptions $queryOptions = null): array
    {
        if (empty($this->geotagid) && !empty($this->address)) {
            $tag = FindGeoTag($this->address);
            $this->geotagid = $queryOptions->getGeotags()->find($tag['GeoTagID']);
        }

        return [new Event($this)];
    }

    public function getKind(): string
    {
        return Itinerary::KIND_RESTAURANT;
    }

    public static function getSegmentMap()
    {
        return [self::SEGMENT_MAP];
    }

    public function getPhones()
    {
        $phones = [];

        if (null !== ($phone = $this->getPhone())) {
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

        if (!empty($this->geotagid)) {
            $result[] = $this->geotagid;
        }

        return $result;
    }

    public function getUTCStartDate()
    {
        return Geotag::getLocalDateTimeByGeoTag($this->startdate, $this->geotagid);
    }

    public function getUTCEndDate()
    {
        return Geotag::getLocalDateTimeByGeoTag($this->enddate, $this->geotagid);
    }

    public function getGuestCount(): ?int
    {
        return $this->guestCount;
    }

    public function setGuestCount(?int $guestCount): void
    {
        $this->guestCount = $guestCount;
    }

    /**
     * @return string[]|null
     */
    public function getSeats(): ?array
    {
        return $this->seats;
    }

    /**
     * @param string[]|null $seats
     */
    public function setSeats(?array $seats): self
    {
        $this->seats = $seats;

        return $this;
    }

    public function getFax(): ?string
    {
        return $this->fax;
    }

    public function setFax(?string $fax): void
    {
        $this->fax = $fax;
    }

    /**
     * @return string flight, reservation, rental, etc
     */
    public function getType(): string
    {
        return 'event';
    }

    public static function getTranslationMessages()
    {
        return [
            (new Message(
                'event.dates-inconsistent',
                'validators'
            ))->setDesc('End date cannot precede the start date.'),
        ];
    }
}
