<?php

namespace AwardWallet\MainBundle\Entity;

use AwardWallet\Common\DateTimeUtils;
use AwardWallet\Common\Entity\Geotag;
use AwardWallet\MainBundle\Loyalty\AccountSaving\Sources\SourceListInterface;
use AwardWallet\MainBundle\Timeline\Item\Checkin;
use AwardWallet\MainBundle\Timeline\Item\Checkout;
use AwardWallet\MainBundle\Timeline\QueryOptions;
use AwardWallet\MainBundle\Timeline\SegmentSourceInterface;
use Doctrine\ORM\Mapping as ORM;
use JMS\TranslationBundle\Model\Message;
use JMS\TranslationBundle\Translation\TranslationContainerInterface;

/**
 * Reservation.
 *
 * @ORM\Table(name="Reservation")
 * @ORM\EntityListeners({ "AwardWallet\MainBundle\Entity\Listener\ItineraryListener" })
 * @ORM\Entity(repositoryClass="AwardWallet\MainBundle\Entity\Repositories\ReservationRepository")
 * @ORM\AttributeOverrides({
 *      @ORM\AttributeOverride(name="id",
 *          column=@ORM\Column(name = "ReservationID", type="integer", nullable=false)
 *      ),
 *      @ORM\AttributeOverride(name="confirmationNumber",
 *          column=@ORM\Column(name = "ConfirmationNumber", type="string", length=100, nullable=false)
 *      ),
 * })
 */
class Reservation extends Itinerary implements SegmentSourceInterface, TranslationContainerInterface, SourceListInterface, ShowAIWarningForEmailSourceInterface
{
    use SourceTrait;
    use AIWarningTrait;

    public const SEGMENT_MAP_START = 'CI';
    public const SEGMENT_MAP_END = 'CO';

    /**
     * @var string
     * @ORM\Column(name="HotelName", type="string", length=80, nullable=false)
     */
    protected $hotelname;

    /**
     * @var string
     * @ORM\Column(name="ChainName", type="string", length=150, nullable=true)
     */
    protected $chainName;

    /**
     * @var \DateTime
     * @ORM\Column(name="CheckInDate", type="datetime", nullable=false)
     */
    protected $checkindate;

    /**
     * @var \DateTime
     * @ORM\Column(name="CheckOutDate", type="datetime", nullable=false)
     */
    protected $checkoutdate;

    /**
     * @var string
     * TODO maybe deprecate in favor of GeoTag
     * @ORM\Column(name="Address", type="string", length=250, nullable=true)
     */
    protected $address;

    /**
     * @var string
     * @ORM\Column(name="Phone", type="string", length=80, nullable=true)
     */
    protected $phone;

    /**
     * @var \DateTime
     * @ORM\Column(name="LastChangeDate", type="datetime", nullable=true)
     */
    protected $lastchangedate;

    /**
     * @var Geotag
     * @ORM\ManyToOne(targetEntity="\AwardWallet\Common\Entity\Geotag")
     * @ORM\JoinColumns({
     *   @ORM\JoinColumn(name="GeoTagID", referencedColumnName="GeoTagID")
     * })
     */
    protected $geotagid;

    /**
     * @var Travelplan
     * @ORM\ManyToOne(targetEntity="Travelplan")
     * @ORM\JoinColumns({
     *   @ORM\JoinColumn(name="TravelPlanID", referencedColumnName="TravelPlanID")
     * })
     */
    protected $travelplanid;

    /**
     * @var \DateTime
     * @ORM\Column(name="ChangeDate", type="datetime", nullable=true)
     */
    protected $changedate;

    /**
     * @var int
     * @ORM\Column(name="FreeNights", type="integer", nullable=false)
     */
    protected $freeNights = 0;

    /**
     * @var string
     * @ORM\Column(name="CancellationNumber", type="string", length=150, nullable=true)
     */
    protected $cancellationNumber;

    /**
     * @var \DateTime
     * @ORM\Column(name="CancellationDeadline", type="datetime")
     */
    protected $cancellationDeadline;

    /**
     * @var bool|null
     * @ORM\Column(name="NonRefundable", type="boolean", nullable=true)
     */
    protected $nonRefundable;

    /**
     * @var string|null
     * @ORM\Column(type="string", name="Fax")
     */
    private $fax;

    /**
     * @var int|null
     * @ORM\Column(type="integer", name="GuestCount")
     */
    private $guestCount;

    /**
     * @var int|null
     * @ORM\Column(type="integer", name="KidsCount")
     */
    private $kidsCount;

    /**
     * @var Room[]
     * @ORM\Column(type="array", name="Rooms")
     */
    private $rooms = [];

    /**
     * @var int|null
     * @ORM\Column(type="integer", name="RoomCount")
     */
    private $roomCount;

    /**
     * @var HotelPointValue|null
     * @ORM\OneToOne(targetEntity="HotelPointValue", mappedBy="reservation", cascade={"persist", "remove", "refresh"})
     */
    private $hotelPointValue;

    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Set hotelname.
     *
     * @param string $hotelname
     * @return Reservation
     */
    public function setHotelname($hotelname)
    {
        $this->hotelname = $hotelname;

        return $this;
    }

    /**
     * Get hotelname.
     *
     * @return string
     */
    public function getHotelname()
    {
        return $this->hotelname;
    }

    public function getChainName(): ?string
    {
        return $this->chainName;
    }

    public function setChainName(?string $chainName): self
    {
        $this->chainName = $chainName;

        return $this;
    }

    /**
     * Set checkindate.
     *
     * @param \DateTime $checkindate
     * @return Reservation
     */
    public function setCheckindate($checkindate)
    {
        if ($checkindate->format('H:i') === '00:00') {
            $checkindate->modify('16:00');
        }
        $this->checkindate = $checkindate;

        return $this;
    }

    /**
     * Get checkindate.
     *
     * @return \DateTime
     */
    public function getCheckindate()
    {
        return $this->checkindate;
    }

    /**
     * Set checkoutdate.
     *
     * @param \DateTime $checkoutdate
     * @return Reservation
     */
    public function setCheckoutdate($checkoutdate)
    {
        if ($checkoutdate->format('H:i') === '00:00') {
            $checkoutdate->modify('11:00');
        }
        $this->checkoutdate = $checkoutdate;

        return $this;
    }

    /**
     * Get checkoutdate.
     *
     * @return \DateTime
     */
    public function getCheckoutdate()
    {
        return $this->checkoutdate;
    }

    /**
     * Set address.
     *
     * @param string $address
     * @return Reservation
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
        if (null !== $this->geotagid) {
            return $this->geotagid->getAddress();
        }

        return $this->address;
    }

    public function setGeoTag(?Geotag $geoTag): void
    {
        if (null !== $geoTag) {
            $this->address = $geoTag->getAddress();
        } else {
            $this->address = null;
        }

        $this->geotagid = $geoTag;
    }

    /**
     * Set lastchangedate.
     *
     * @param \DateTime $lastchangedate
     * @return Reservation
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
     * Set geotagid.
     *
     * @return Reservation
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
     * @return Reservation
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

        $checkIn = new Checkin($this);
        $checkOut = new Checkout($this);

        $checkIn->setConnection($checkOut);
        $checkOut->setConnection($checkIn);

        return [$checkIn, $checkOut];
    }

    public function getNights()
    {
        return self::getNightCount($this->checkindate, $this->checkoutdate);
    }

    public static function getNightCount(\DateTime $startDate, \DateTime $endDate): int
    {
        $startDate = clone $startDate;

        if ($startDate->format('H:i') < '06:00') {
            $startDate->modify('-1 day');
        }

        return max(
            (strtotime($endDate->format('Y-m-d')) - strtotime($startDate->format('Y-m-d'))) / DateTimeUtils::SECONDS_PER_DAY,
            1
        );
    }

    public function getKind(): string
    {
        return Itinerary::KIND_RESERVATION;
    }

    public static function getSegmentMap()
    {
        return [self::SEGMENT_MAP_START, self::SEGMENT_MAP_END];
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

    public function getStartDate()
    {
        return $this->checkindate;
    }

    public function getEndDate()
    {
        return $this->checkoutdate;
    }

    public function getUTCStartDate()
    {
        return Geotag::getLocalDateTimeByGeoTag($this->checkindate, $this->geotagid);
    }

    public function getUTCEndDate()
    {
        return Geotag::getLocalDateTimeByGeoTag($this->checkoutdate, $this->geotagid);
    }

    /**
     * @return Message[]
     */
    public static function getTranslationMessages()
    {
        return [
            (new Message(
                'reservation.dates-inconsistent',
                'validators'
            ))->setDesc('Check-out date cannot precede the check-in date.'),
        ];
    }

    public function getFax(): ?string
    {
        return $this->fax;
    }

    public function setFax(?string $fax): void
    {
        $this->fax = $fax;
    }

    public function getGuestCount(): ?int
    {
        return $this->guestCount;
    }

    public function setGuestCount(?int $guestCount): void
    {
        $this->guestCount = $guestCount;
    }

    public function getKidsCount(): ?int
    {
        return $this->kidsCount;
    }

    public function setKidsCount(?int $kidsCount): void
    {
        $this->kidsCount = $kidsCount;
    }

    /**
     * @return Room[]
     */
    public function getRooms(): array
    {
        // TODO remove when nulls are no longer possible
        if (null === $this->rooms) {
            return [];
        }

        return $this->rooms;
    }

    /**
     * @param Room[] $rooms
     */
    public function setRooms(array $rooms): void
    {
        $this->rooms = $rooms;
    }

    public function getRoomCount(): ?int
    {
        return $this->roomCount;
    }

    public function setRoomCount(?int $roomCount): void
    {
        $this->roomCount = $roomCount;
    }

    /**
     * @return string flight, reservation, rental, etc
     */
    public function getType(): string
    {
        return 'hotel_reservation';
    }

    public function getCancellationNumber(): ?string
    {
        return $this->cancellationNumber;
    }

    public function setCancellationNumber(?string $cancellationNumber): self
    {
        $this->cancellationNumber = $cancellationNumber;

        return $this;
    }

    public function getCancellationDeadline(): ?\DateTime
    {
        return $this->cancellationDeadline;
    }

    public function setCancellationDeadline(\DateTime $cancellationDeadline): void
    {
        $this->cancellationDeadline = $cancellationDeadline;
    }

    public function getNonRefundable(): ?bool
    {
        return $this->nonRefundable;
    }

    public function setNonRefundable(?bool $nonRefundable): self
    {
        $this->nonRefundable = $nonRefundable;

        return $this;
    }

    public function getHotelPointValue(): ?HotelPointValue
    {
        return $this->hotelPointValue;
    }

    public function getFreeNights(): int
    {
        return $this->freeNights;
    }

    public function setFreeNights(int $freeNights): Reservation
    {
        $this->freeNights = $freeNights;

        return $this;
    }
}
