<?php

namespace AwardWallet\MainBundle\Entity;

use AwardWallet\Common\Entity\Aircode;
use AwardWallet\Common\Entity\Geotag;
use AwardWallet\MainBundle\Loyalty\AccountSaving\Sources\SourceListInterface;
use AwardWallet\MainBundle\Timeline\Item\AirTrip;
use AwardWallet\MainBundle\Timeline\Item\BusTrip;
use AwardWallet\MainBundle\Timeline\Item\CruiseTrip;
use AwardWallet\MainBundle\Timeline\Item\FerryTrip;
use AwardWallet\MainBundle\Timeline\Item\TrainTrip;
use AwardWallet\MainBundle\Timeline\Item\Transfer;
use AwardWallet\MainBundle\Timeline\QueryOptions;
use AwardWallet\MainBundle\Timeline\SegmentSourceInterface;
use Doctrine\ORM\Mapping as ORM;
use Doctrine\Persistence\Event\PreUpdateEventArgs;
use JMS\Serializer\Annotation as JMS;
use JMS\TranslationBundle\Model\Message;
use JMS\TranslationBundle\Translation\TranslationContainerInterface;

/**
 * Tripsegment.
 *
 * @ORM\Table(name="TripSegment")
 * @ORM\Entity(repositoryClass="AwardWallet\MainBundle\Entity\Repositories\TripsegmentRepository")
 * @ORM\EntityListeners({"AwardWallet\MainBundle\Entity\Listener\TripsegmentListener"})
 * @JMS\ExclusionPolicy("all")
 */
class Tripsegment implements SegmentSourceInterface, GeotagInterface, DateRangeInterface, TranslationContainerInterface, Cancellable, ContainsConfirmationNumbers, SourceListInterface, ShowAIWarningForEmailSourceInterface
{
    use SourceTrait;
    use AIWarningTrait;

    public const IATA_PATTERN = '#^[a-z]{3}$#ims';
    public const SOURCE_EMAIL = 'E';

    private const NOT_HIDDEN = 0;
    private const HIDDEN_BY_UPDATER = 1;
    private const HIDDEN_BY_USER = 2;

    /**
     * @var int
     * @ORM\Column(name="TripSegmentID", type="integer", nullable=false)
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="IDENTITY")
     */
    protected $tripsegmentid;

    /**
     * @var string
     * @ORM\Column(name="DepCode", type="string", length=10, nullable=true)
     */
    protected $depcode;

    /**
     * For now used only with forms.
     *
     * @JMS\Type("AwardWallet\Common\Entity\Aircode")
     * @JMS\Expose()
     * @JMS\Groups({"basic"})
     * @var Aircode
     */
    protected $departureAirport;

    /**
     * @var string
     * @ORM\Column(name="DepName", type="string", length=250, nullable=false)
     */
    protected $depname;

    /**
     * @var \DateTime
     * @JMS\Expose()
     * @JMS\Groups({"basic"})
     *
     * TODO bring back date consistency check when possible
     * @ORM\Column(name="DepDate", type="datetime", nullable=false)
     */
    protected $depdate;

    /**
     * @var string
     * @ORM\Column(name="ArrCode", type="string", length=10, nullable=true)
     */
    protected $arrcode;

    /**
     * For now used only with forms.
     *
     * @JMS\Type("AwardWallet\Common\Entity\Aircode")
     * @JMS\Expose()
     * @JMS\Groups({"basic"})
     * @var Aircode
     */
    protected $arrivalAirport;

    /**
     * @var string
     * @ORM\Column(name="ArrName", type="string", length=250, nullable=false)
     */
    protected $arrname;

    /**
     * @var \DateTime
     * @JMS\Expose()
     * @JMS\Groups({"basic"})
     * @ORM\Column(name="ArrDate", type="datetime", nullable=false)
     */
    protected $arrdate;

    /**
     * @var string
     * @JMS\Expose()
     * @JMS\Groups({"basic"})
     * @ORM\Column(name="AirlineName", type="string", length=250, nullable=true)
     */
    protected $airlineName;

    /**
     * @var Airline|null
     * @ORM\ManyToOne(targetEntity="AwardWallet\MainBundle\Entity\Airline")
     * @ORM\JoinColumn(name="AirlineID", referencedColumnName="AirlineID")
     */
    protected $airline;

    /**
     * @var string
     * @JMS\Expose()
     * @JMS\Groups({"basic"})
     * @ORM\Column(name="FlightNumber", type="string", length=20, nullable=true)
     */
    protected $flightNumber;

    /**
     * @var string
     * @ORM\Column(name="BoardingPassURL", type="string", length=255, nullable=true)
     */
    protected $boardingpassurl;

    /**
     * @var \DateTime
     * @ORM\Column(name="PreCheckinNotificationDate", type="datetime", nullable=true)
     */
    protected $preCheckinNotificationDate;

    /**
     * @var \DateTime
     * @ORM\Column(name="CheckinNotificationDate", type="datetime", nullable=true)
     */
    protected $checkinnotificationdate;

    /**
     * @var \DateTime
     * @ORM\Column(name="FlightDepartureNotificationDate", type="datetime", nullable=true)
     */
    protected $flightDepartureNotificationDate;

    /**
     * @var \DateTime
     * @ORM\Column(name="FlightBoardingNotificationDate", type="datetime", nullable=true)
     */
    protected $flightBoardingNotificationDate;

    /**
     * @var Geotag
     * @ORM\ManyToOne(targetEntity="\AwardWallet\Common\Entity\Geotag", cascade={"persist"})
     * @ORM\JoinColumns({
     *   @ORM\JoinColumn(name="ArrGeoTagID", referencedColumnName="GeoTagID")
     * })
     */
    protected $arrgeotagid;

    /**
     * @var Geotag
     * @ORM\ManyToOne(targetEntity="\AwardWallet\Common\Entity\Geotag", cascade={"persist"})
     * @ORM\JoinColumns({
     *   @ORM\JoinColumn(name="DepGeoTagID", referencedColumnName="GeoTagID")
     * })
     */
    protected $depgeotagid;

    /**
     * @var Travelplan
     * @ORM\ManyToOne(targetEntity="Travelplan")
     * @ORM\JoinColumns({
     *   @ORM\JoinColumn(name="TravelPlanID", referencedColumnName="TravelPlanID")
     * })
     */
    protected $travelplanid;

    /**
     * @var Trip
     * @ORM\ManyToOne(targetEntity="Trip", inversedBy="segments", cascade={"persist"})
     * @ORM\JoinColumns({
     *   @ORM\JoinColumn(name="TripID", referencedColumnName="TripID")
     * })
     */
    protected $tripid;

    /**
     * @var FlightInfo
     * @ORM\ManyToOne(targetEntity="FlightInfo", inversedBy="Segments")
     * @ORM\JoinColumns({
     *   @ORM\JoinColumn(name="FlightInfoID", referencedColumnName="FlightInfoID")
     * })
     */
    protected $flightinfoid;

    /**
     * @var \DateTime
     * @ORM\Column(name="ChangeDate", type="datetime", nullable=true)
     */
    protected $ChangeDate;

    /**
     * @var \DateTime
     * @ORM\Column(name="ScheduledDepDate", type="datetime", nullable=true)
     */
    protected $scheduledDepDate;

    /**
     * @var \DateTime
     * @ORM\Column(name="ScheduledArrDate", type="datetime", nullable=true)
     */
    protected $scheduledArrDate;

    /**
     * @var \DateTime
     * @ORM\Column(name="TripAlertsUpdateDate", type="datetime")
     */
    protected $tripAlertsUpdateDate;

    /**
     * @var bool
     * @ORM\Column(name="Undeleted", type="boolean", nullable=false)
     */
    protected $undeleted = false;

    /**
     * @var string
     * @JMS\Expose()
     * @JMS\Groups({"basic"})
     * @ORM\Column(name="OperatingAirlineName", type="string", length=250, nullable=true)
     */
    private $operatingAirlineName;

    /**
     * @var Airline|null
     * @ORM\ManyToOne(targetEntity="AwardWallet\MainBundle\Entity\Airline")
     * @ORM\JoinColumn(name="OperatingAirlineID", referencedColumnName="AirlineID")
     */
    private $operatingAirline;

    /**
     * @var string
     * @JMS\Expose()
     * @JMS\Groups({"basic"})
     * @ORM\Column(name="OperatingAirlineFlightNumber", type="string", length=20, nullable=true)
     */
    private $operatingAirlineFlightNumber;

    /**
     * @var int
     * @ORM\Column(name="Hidden", type="integer", nullable=false)
     */
    private $hidden = self::NOT_HIDDEN;

    /**
     * @var Aircraft|null
     * @ORM\ManyToOne(targetEntity="Aircraft")
     * @ORM\JoinColumn(name="AircraftID", referencedColumnName="AircraftID")
     */
    private $aircraft;

    /**
     * @var string|null
     * @ORM\Column(type="string", name="Aircraft")
     */
    private $aircraftName;

    /**
     * @var string|null
     * @ORM\Column(name="Vessel", type="string", nullable=true)
     */
    private $vessel;

    /**
     * @var string|null
     * @ORM\Column(type="string", name="ArrivalGate")
     */
    private $arrivalGate;

    /**
     * @var string|null
     * @ORM\Column(type="string", name="DepartureGate")
     */
    private $departureGate;

    /**
     * @var string|null
     * @ORM\Column(type="string", name="DepartureTerminal")
     */
    private $departureTerminal;

    /**
     * @var string|null
     * @ORM\Column(type="string", name="ArrivalTerminal")
     */
    private $arrivalTerminal;

    /**
     * @var string|null
     * @ORM\Column(type="string", name="BaggageClaim")
     */
    private $baggageClaim;

    /**
     * @var string|null
     * @ORM\Column(type="string", name="BookingClass")
     */
    private $bookingClass;

    /**
     * @var string|null
     * @ORM\Column(type="string", name="CabinClass")
     */
    private $cabinClass;

    /**
     * @var string|null
     * @ORM\Column(type="string", name="Duration")
     */
    private $duration;

    /**
     * @var bool|null
     * @ORM\Column(type="boolean", name="Smoking")
     */
    private $smoking;

    /**
     * @var int|null
     * @ORM\Column(type="integer", name="Stops")
     */
    private $stops;

    /**
     * @var string|null
     * @ORM\Column(type="string", name="TraveledMiles")
     */
    private $traveledMiles;

    /**
     * @var string|null
     * @ORM\Column(type="string", name="Meal")
     */
    private $meal;

    /**
     * @var string[]
     * @ORM\Column(type="simple_array", name="Seats")
     */
    private $seats = [];

    /**
     * @var string[]|null
     * @ORM\Column(name="Accommodations", type="json", nullable=true)
     */
    private $accommodations;

    /**
     * @var string[]
     * @ORM\Column(type="simple_array", name="ServiceClasses")
     */
    private $serviceClasses = [];

    /**
     * @var string|null
     * @ORM\Column(type="string", name="SourceKind")
     */
    private $sourceKind;

    /**
     * @var string|null
     * @ORM\Column(type="string", name="SourceID")
     */
    private $sourceId;

    /**
     * @var string|null
     * @ORM\Column(name="OperatingAirlineConfirmationNumber", type="string")
     */
    private $operatingAirlineConfirmationNumber;

    /**
     * @var string|null
     * @ORM\Column(name="MarketingAirlineConfirmationNumber", type="string")
     */
    private $marketingAirlineConfirmationNumber;

    /**
     * @var string[]
     * @ORM\Column(name="OperatingAirlinePhoneNumbers", type="simple_array")
     */
    private $operatingAirlinePhoneNumbers;

    /**
     * @var string[]
     * @ORM\Column(name="MarketingAirlinePhoneNumbers", type="simple_array")
     */
    private $marketingAirlinePhoneNumbers;

    /**
     * @ORM\Column(name="AdultsCount", type="integer")
     * @var int|null
     */
    private $adultsCount;

    /**
     * @var int|null
     * @ORM\Column(name="KidsCount", type="integer")
     */
    private $kidsCount;

    /**
     * @var string|null
     * @ORM\Column(name="Pets", type="string", nullable=true)
     */
    private $pets;

    /**
     * @var Vehicle[]|null
     * @ORM\Column(name="Vehicles", type="jms_json", nullable=true)
     */
    private $vehicles;

    /**
     * @var Vehicle[]|null
     * @ORM\Column(name="Trailers", type="jms_json", nullable=true)
     */
    private $trailers;

    /**
     * @var string|null
     * @ORM\Column(name="ServiceName", type="string", length=50)
     */
    private $serviceName;

    /**
     * @var string|null
     * @ORM\Column(name="CarNumber", type="string", length=20)
     */
    private $carNumber;

    /**
     * @var Airline|null
     * @ORM\ManyToOne(targetEntity="Airline")
     * @ORM\JoinColumn(name="WetLeaseAirlineID", referencedColumnName="AirlineID")
     */
    private $wetLeaseAirline;

    /**
     * @var string|null
     * @ORM\Column(name="WetLeaseAirlineName", type="string", length=100)
     */
    private $wetLeaseAirlineName;

    /**
     * @var string|null
     * @ORM\Column(type="string", length=20)
     */
    private $parsedStatus;

    public function __clone()
    {
        $this->tripsegmentid = null;
        $this->travelplanid = null;

        if ($this->depdate) {
            $this->depdate = clone $this->depdate;
        }

        if ($this->arrdate) {
            $this->arrdate = clone $this->arrdate;
        }

        if (null !== $this->checkinnotificationdate) {
            $this->checkinnotificationdate = clone $this->checkinnotificationdate;
        }
        $this->tripid = null;
        $this->flightinfoid = null;

        if (null !== $this->ChangeDate) {
            $this->ChangeDate = clone $this->ChangeDate;
        }

        if ($this->scheduledDepDate) {
            $this->scheduledArrDate = clone $this->scheduledArrDate;
        }

        if ($this->scheduledDepDate) {
            $this->scheduledDepDate = clone $this->scheduledDepDate;
        }
    }

    /**
     * Get tripsegmentid.
     *
     * @return int
     */
    public function getTripsegmentid()
    {
        return $this->tripsegmentid;
    }

    /**
     * @return int
     */
    public function getId()
    {
        return $this->tripsegmentid;
    }

    /**
     * Set depcode.
     *
     * @param string $depcode
     * @return Tripsegment
     */
    public function setDepcode($depcode)
    {
        $this->depcode = $depcode;

        return $this;
    }

    /**
     * Get depcode.
     *
     * @return string
     */
    public function getDepcode()
    {
        return $this->depcode;
    }

    /**
     * Set depname.
     *
     * @param string $depname
     * @return Tripsegment
     */
    public function setDepname($depname)
    {
        $this->depname = $depname;

        return $this;
    }

    /**
     * Get depname.
     *
     * @return string
     */
    public function getDepname()
    {
        return $this->depname;
    }

    /**
     * Set depdate.
     *
     * @return Tripsegment
     * @deprecated use setDepartureDate() instead
     */
    public function setDepdate(\DateTime $depdate)
    {
        $this->setDepartureDate($depdate);

        return $this;
    }

    public function setDepartureDate(\DateTime $date)
    {
        $this->depdate = $date;

        if (null === $this->scheduledDepDate) {
            $this->scheduledDepDate = $date;
        }

        return $this;
    }

    /**
     * Get depdate.
     *
     * @return \DateTime|null
     * @deprecated  use getDepartureDate() instead
     */
    public function getDepdate()
    {
        return $this->getDepartureDate();
    }

    public function getDepartureDate(): \DateTime
    {
        return $this->depdate;
    }

    /**
     * Set arrcode.
     *
     * @param string $arrcode
     * @return Tripsegment
     */
    public function setArrcode($arrcode)
    {
        $this->arrcode = $arrcode;

        return $this;
    }

    /**
     * Get arrcode.
     *
     * @return string
     */
    public function getArrcode()
    {
        return $this->arrcode;
    }

    /**
     * Set arrname.
     *
     * @param string $arrname
     * @return Tripsegment
     */
    public function setArrname($arrname)
    {
        $this->arrname = $arrname;

        return $this;
    }

    /**
     * Get arrname.
     *
     * @return string
     */
    public function getArrname()
    {
        return $this->arrname;
    }

    /**
     * Set arrdate.
     *
     * @return Tripsegment
     * @deprecated use setArrivalDate() instead
     */
    public function setArrdate(\DateTime $arrdate)
    {
        $this->setArrivalDate($arrdate);

        return $this;
    }

    public function setArrivalDate(\DateTime $date)
    {
        $this->arrdate = $date;

        if (null === $this->scheduledArrDate) {
            $this->scheduledArrDate = $date;
        }

        return $this;
    }

    /**
     * Get arrdate.
     *
     * @return \DateTime|null
     * @deprecated use getArrivalDate() instead
     */
    public function getArrdate()
    {
        return $this->getArrivalDate();
    }

    public function getArrivalDate(): \DateTime
    {
        return $this->arrdate;
    }

    public function getFlightNumber(): ?string
    {
        return $this->flightNumber;
    }

    public function setFlightNumber(?string $flightNumber): void
    {
        $this->flightNumber = $flightNumber;
    }

    public function getOperatingAirlineFlightNumber(): ?string
    {
        return $this->operatingAirlineFlightNumber;
    }

    public function setOperatingAirlineFlightNumber(?string $flightNumber): void
    {
        $this->operatingAirlineFlightNumber = $flightNumber;
    }

    /**
     * @return string
     */
    public function getBoardingpassurl()
    {
        return $this->boardingpassurl;
    }

    /**
     * @param string $boardingpassurl
     * @return Tripsegment
     */
    public function setBoardingpassurl($boardingpassurl)
    {
        $this->boardingpassurl = $boardingpassurl;

        return $this;
    }

    public function getPreCheckinNotificationDate(): ?\DateTime
    {
        return $this->preCheckinNotificationDate;
    }

    public function setPreCheckinNotificationDate(?\DateTime $preCheckinNotificationDate): self
    {
        $this->preCheckinNotificationDate = $preCheckinNotificationDate;

        return $this;
    }

    /**
     * @return \DateTime
     */
    public function getCheckinnotificationdate()
    {
        return $this->checkinnotificationdate;
    }

    /**
     * @return Tripsegment
     */
    public function setCheckinnotificationdate(?\DateTime $checkinnotificationdate = null)
    {
        $this->checkinnotificationdate = $checkinnotificationdate;

        return $this;
    }

    public function getFlightDepartureNotificationDate(): ?\DateTime
    {
        return $this->flightDepartureNotificationDate;
    }

    public function setFlightDepartureNotificationDate(?\DateTime $flightDepartureNotificationDate): self
    {
        $this->flightDepartureNotificationDate = $flightDepartureNotificationDate;

        return $this;
    }

    public function getFlightBoardingNotificationDate(): ?\DateTime
    {
        return $this->flightBoardingNotificationDate;
    }

    public function setFlightBoardingNotificationDate(?\DateTime $flightBoardingNotificationDate): self
    {
        $this->flightBoardingNotificationDate = $flightBoardingNotificationDate;

        return $this;
    }

    /**
     * Set arrgeotagid.
     *
     * @return Tripsegment
     */
    public function setArrgeotagid(?Geotag $arrgeotagid = null)
    {
        $this->arrgeotagid = $arrgeotagid;

        return $this;
    }

    /**
     * Get arrgeotagid.
     *
     * @return Geotag
     */
    public function getArrgeotagid()
    {
        return $this->arrgeotagid;
    }

    /**
     * Set depgeotagid.
     *
     * @return Tripsegment
     */
    public function setDepgeotagid(?Geotag $depgeotagid = null)
    {
        $this->depgeotagid = $depgeotagid;

        return $this;
    }

    /**
     * Get depgeotagid.
     *
     * @return Geotag
     */
    public function getDepgeotagid()
    {
        return $this->depgeotagid;
    }

    /**
     * Set travelplanid.
     *
     * @return Tripsegment
     */
    public function setTravelplanid(?Travelplan $travelplanid = null)
    {
        $this->travelplanid = $travelplanid;

        return $this;
    }

    /**
     * Get travelplanid.
     *
     * @return Travelplan
     */
    public function getTravelplanid()
    {
        return $this->travelplanid;
    }

    /**
     * Set tripid.
     *
     * @return Tripsegment
     */
    public function setTripid(Trip $tripid)
    {
        if ($this->tripid !== $tripid) {
            $this->tripid = $tripid;
            $tripid->addSegment($this);
        }

        return $this;
    }

    /**
     * Get tripid.
     *
     * @return Trip
     */
    public function getTripid()
    {
        return $this->tripid;
    }

    /**
     * @deprecated use formatter instead
     */
    public function getName()
    {
        if (empty($this->airlineName)) {
            $result = $this->tripid->getAirlineName();

            if (empty($result) && !empty($this->tripid->getProvider())) {
                $result = $this->tripid->getProvider()->getShortname();
            }
        } else {
            $result = $this->airlineName;
        }

        if (!empty($this->flightNumber)) {
            $result .= ' ' . $this->flightNumber;
        }

        return $result;
    }

    public function getTimelineItems(Usr $user, ?QueryOptions $queryOptions = null): array
    {
        if ($this->hidden !== self::NOT_HIDDEN && !$queryOptions->isShowDeleted()) {
            return [];
        }

        $tripCategory = $this->tripid->getCategory();

        if (empty($this->depgeotagid)) {
            if (TRIP_CATEGORY_AIR === $tripCategory && !empty($this->depcode)) {
                $tag = FindGeoTag($this->depcode, null, TRIP_CATEGORY_AIR);
            } else {
                $tag = FindGeoTag($this->depname);
            }
            $this->depgeotagid = $queryOptions->getGeotags()->find($tag['GeoTagID']);
        }

        if (empty($this->arrgeotagid)) {
            if (TRIP_CATEGORY_AIR === $tripCategory && !empty($this->arrcode)) {
                $tag = FindGeoTag($this->arrcode, null, TRIP_CATEGORY_AIR);
            } else {
                $tag = FindGeoTag($this->arrname);
            }
            $this->arrgeotagid = $queryOptions->getGeotags()->find($tag['GeoTagID']);
        }

        switch ($tripCategory) {
            case TRIP_CATEGORY_BUS:
                $trip = new BusTrip($this);

                break;

            case TRIP_CATEGORY_CRUISE:
                $trip = new CruiseTrip($this);

                break;

            case TRIP_CATEGORY_FERRY:
                $trip = new FerryTrip($this);

                break;

            case TRIP_CATEGORY_TRAIN:
                $trip = new TrainTrip($this);

                break;

            case TRIP_CATEGORY_AIR:
                $trip = new AirTrip($this, $queryOptions->getOperatedByResolver()->resolveAirProvider($this));

                break;

            case TRIP_CATEGORY_TRANSFER:
                $trip = new Transfer($this);

                break;

            default:
                throw new \RuntimeException(sprintf('Invalid trip category "%s"', $tripCategory));
        }

        return [$trip];
    }

    public function getChangeDate()
    {
        return $this->ChangeDate;
    }

    /**
     * @return $this
     */
    public function setChangeDate(?\DateTime $value = null)
    {
        $this->ChangeDate = $value;

        return $this;
    }

    public function getUser()
    {
        return $this->getTripid()->getUser();
    }

    public function getNotes()
    {
        return $this->tripid->getNotes();
    }

    public function getHidden(): bool
    {
        return $this->hidden !== self::NOT_HIDDEN;
    }

    /**
     * @param bool $withCode
     * @return string
     */
    public function getArrAirportName($withCode = true)
    {
        if ($this->getTripid()->getCategory() == TRIP_CATEGORY_AIR) {
            return $this->getAirportName("arr", $withCode);
        }

        return $this->getArrname();
    }

    /**
     * @param bool $withCode
     * @return string
     */
    public function getDepAirportName($withCode = true)
    {
        if ($this->getTripid()->getCategory() == TRIP_CATEGORY_AIR) {
            return $this->getAirportName("dep", $withCode);
        }

        return $this->getDepname();
    }

    /**
     * @return string
     */
    public function getKind()
    {
        return 'S';
    }

    /**
     * @return FlightInfo
     */
    public function getFlightinfoid()
    {
        return $this->flightinfoid;
    }

    public function setFlightinfoid(?FlightInfo $flightinfoid = null)
    {
        $this->flightinfoid = $flightinfoid;
    }

    public function getGeoTags()
    {
        $geotags = [];

        if ($this->depgeotagid) {
            $geotags[] = $this->depgeotagid;
        }

        if ($this->arrgeotagid) {
            $geotags[] = $this->arrgeotagid;
        }

        return $geotags;
    }

    public function getStartDate()
    {
        return $this->depdate;
    }

    public function getEndDate()
    {
        return $this->arrdate;
    }

    public function getUTCStartDate()
    {
        return Geotag::getLocalDateTimeByGeoTag($this->depdate, $this->depgeotagid);
    }

    public function getUTCEndDate()
    {
        return Geotag::getLocalDateTimeByGeoTag($this->arrdate, $this->arrgeotagid);
    }

    /**
     * @return \DateTime
     */
    public function getScheduledDepDate()
    {
        return $this->scheduledDepDate;
    }

    public function setScheduledDepDate(\DateTime $scheduledDepDate)
    {
        $this->scheduledDepDate = $scheduledDepDate;
    }

    /**
     * @return \DateTime
     */
    public function getScheduledArrDate()
    {
        return $this->scheduledArrDate;
    }

    public function setScheduledArrDate(\DateTime $scheduledArrDate)
    {
        $this->scheduledArrDate = $scheduledArrDate;
    }

    /**
     * @ORM\PreUpdate
     */
    public function preUpdate(PreUpdateEventArgs $args)
    {
        if (
            $args->hasChangedField('depcode')
            || $args->hasChangedField('depname')
            || $args->hasChangedField('depdate')
            || $args->hasChangedField('arrcode')
            || $args->hasChangedField('arrname')
            || $args->hasChangedField('arrdate')
            || $args->hasChangedField('marketingAirlineName')
            || $args->hasChangedField('marketingAirline')
            || $args->hasChangedField('operatingAirlineName')
            || $args->hasChangedField('operatingAirline')
            || $args->hasChangedField('flightnumber')
            || $args->hasChangedField('boardingpassurl')
            || $args->hasChangedField('travelplanid')
            || $args->hasChangedField('scheduledDepDate')
            || $args->hasChangedField('scheduledArrDate')
        ) {
            $this->ChangeDate = new \DateTime();
        }
    }

    /**
     * For now used only with forms.
     *
     * @return Aircode
     */
    public function getDepartureAirport()
    {
        return $this->departureAirport;
    }

    /**
     * For now used only with forms.
     *
     * @return $this
     */
    public function setDepartureAirport(?Aircode $airport = null)
    {
        if (null === $airport) {
            $this->depcode = null;
            $this->depname = null;
        } else {
            $this->depcode = $airport->getAircode();
            $this->depname = $airport->getAirname();
        }
        $this->departureAirport = $airport;

        return $this;
    }

    /**
     * For now used only with forms.
     *
     * @return Aircode
     */
    public function getArrivalAirport()
    {
        return $this->arrivalAirport;
    }

    /**
     * For now used only with forms.
     *
     * @return $this
     */
    public function setArrivalAirport(?Aircode $airport = null)
    {
        if (null === $airport) {
            $this->depcode = null;
            $this->depname = null;
        } else {
            $this->arrcode = $airport->getAircode();
            $this->arrname = $airport->getAirname();
        }

        $this->arrivalAirport = $airport;

        return $this;
    }

    /**
     * Returns an array of messages.
     *
     * @return array<Message>
     */
    public static function getTranslationMessages()
    {
        return [
            (new Message(
                "itineraries.departure-and-arrival-are-the-same",
                "validators"
            ))->setDesc("Departure and arrival airports can not be the same"),
            (new Message(
                "itineraries.dates-inconsistent",
                "validators"
            ))->setDesc("Arrival date cannot precede the departure date"),
            (new Message(
                'itineraries.dates-inconsistent.train',
                'validators'
            ))->setDesc('Arrival time cannot precede departure time. Please double-check your dates and times and verify that you provided the correct city name or address in the "Departure Station" and "Arrival Station" fields, which are used to detect the timezones.'),
            (new Message(
                "max-length",
                "validators"
            ))->setDesc("This value is too long. It should have {{ limit }} character or less.|This value is too long. It should have {{ limit }} characters or less."),
            (new Message("digit", "validators"))->setDesc("This field should only contain digits"),
        ];
    }

    public function getAirlineName(): ?string
    {
        return $this->airlineName;
    }

    /**
     * Set just the parsed airline name if airline itself cannot be identified.
     */
    public function setAirlineName(?string $airlineName): void
    {
        if (null !== $this->airline && $this->airline->getName() !== $airlineName) {
            $this->airline = null;
        }
        $this->airlineName = $airlineName;
    }

    /**
     * If only the airline name is needed, then @return Airline|null.
     *
     * @see getAirlineName()
     */
    public function getAirline(): ?Airline
    {
        return $this->airline;
    }

    public function setAirline(?Airline $airline, bool $updateName = true): void
    {
        if ($updateName) {
            if (null !== $airline) {
                $this->airlineName = $airline->getName();
            } else {
                $this->airlineName = null;
            }
        }
        $this->airline = $airline;
    }

    public function getOperatingAirlineName(): ?string
    {
        return $this->operatingAirlineName;
    }

    /**
     * Set just the parsed operating airline name if airline itself cannot be identified.
     */
    public function setOperatingAirlineName(?string $airlineName): void
    {
        if (null !== $this->operatingAirline && $this->operatingAirline->getName() !== $airlineName) {
            $this->operatingAirline = null;
        }
        $this->operatingAirlineName = $airlineName;
    }

    /**
     * If only the operating airline name is needed, then @return Airline|null.
     *
     * @see getOperatingAirlineName()
     */
    public function getOperatingAirline(): ?Airline
    {
        return $this->operatingAirline;
    }

    public function setOperatingAirline(?Airline $airline): void
    {
        if (null !== $airline) {
            $this->operatingAirlineName = $airline->getName();
        } else {
            $this->operatingAirlineName = null;
        }
        $this->operatingAirline = $airline;
    }

    /**
     * If only the aircraft name is needed, @return Aircraft|null.
     *
     * @see getAircraftName()
     */
    public function getAircraft(): ?Aircraft
    {
        return $this->aircraft;
    }

    public function setAircraft(?Aircraft $aircraft): void
    {
        if (null !== $aircraft) {
            $this->aircraftName = $aircraft->getName();
        } else {
            $this->aircraftName = null;
        }
        $this->aircraft = $aircraft;
    }

    public function getAircraftName(): ?string
    {
        return $this->aircraftName;
    }

    /**
     * Set just the parsed aircraft name if aircraft itself cannot be identified.
     */
    public function setAircraftName(?string $name): void
    {
        if (null !== $this->aircraft && $this->aircraft->getName() !== $name) {
            $this->aircraft = null;
        }
        $this->aircraftName = $name;
    }

    public function getVessel(): ?string
    {
        return $this->vessel;
    }

    public function setVessel(?string $vessel): self
    {
        $this->vessel = $vessel;

        return $this;
    }

    public function getArrivalGate(): ?string
    {
        return $this->arrivalGate;
    }

    public function setArrivalGate(?string $arrivalGate): void
    {
        $this->arrivalGate = $arrivalGate;
    }

    public function getDepartureGate(): ?string
    {
        return $this->departureGate;
    }

    public function setDepartureGate(?string $departureGate): void
    {
        $this->departureGate = $departureGate;
    }

    public function getDepartureTerminal(): ?string
    {
        return $this->departureTerminal;
    }

    public function setDepartureTerminal(?string $departureTerminal): void
    {
        $this->departureTerminal = $departureTerminal;
    }

    public function getArrivalTerminal(): ?string
    {
        return $this->arrivalTerminal;
    }

    public function setArrivalTerminal(?string $arrivalTerminal): void
    {
        $this->arrivalTerminal = $arrivalTerminal;
    }

    public function getBaggageClaim(): ?string
    {
        return $this->baggageClaim;
    }

    public function setBaggageClaim(?string $baggageClaim): void
    {
        $this->baggageClaim = $baggageClaim;
    }

    public function getBookingClass(): ?string
    {
        return $this->bookingClass;
    }

    public function setBookingClass(?string $bookingClass): void
    {
        $this->bookingClass = $bookingClass;
    }

    public function getCabinClass(): ?string
    {
        return $this->cabinClass;
    }

    public function setCabinClass(?string $cabinClass): void
    {
        $this->cabinClass = $cabinClass;
    }

    public function getDuration(): ?string
    {
        return $this->duration;
    }

    public function setDuration(?string $duration): void
    {
        $this->duration = $duration;
    }

    public function isSmoking(): ?bool
    {
        return $this->smoking;
    }

    public function setSmoking(?bool $smoking): void
    {
        $this->smoking = $smoking;
    }

    public function getStops(): ?int
    {
        return $this->stops;
    }

    public function setStops(?int $stops): void
    {
        $this->stops = $stops;
    }

    public function getTraveledMiles(): ?string
    {
        return $this->traveledMiles;
    }

    public function setTraveledMiles(?string $traveledMiles): void
    {
        $this->traveledMiles = $traveledMiles;
    }

    public function getMeal(): ?string
    {
        return $this->meal;
    }

    public function setMeal(?string $meal): void
    {
        $this->meal = $meal;
    }

    /**
     * @return string[]
     */
    public function getSeats(): array
    {
        return $this->seats;
    }

    /**
     * @param string[] $seats
     */
    public function setSeats(array $seats): void
    {
        $this->seats = $seats;
        sort($this->seats);
    }

    /**
     * @return string[]|null
     */
    public function getAccommodations(): ?array
    {
        return $this->accommodations;
    }

    /**
     * @param string[]|null $accommodations
     */
    public function setAccommodations(?array $accommodations): self
    {
        $this->accommodations = $accommodations;

        return $this;
    }

    /**
     * @return string[]|null
     */
    public function getParsedAccountNumbers(): ?array
    {
        return $this->tripid->getParsedAccountNumbers();
    }

    /**
     * @return string[]|null
     */
    public function getTravelAgencyParsedAccountNumbers(): ?array
    {
        return $this->tripid->getTravelAgencyParsedAccountNumbers();
    }

    public function getPricingInfo(): ?PricingInfo
    {
        return $this->tripid->getPricingInfo();
    }

    public function getReservationDate(): ?\DateTime
    {
        return $this->tripid->getReservationDate();
    }

    /**
     * @return string[]
     */
    public function getTravelerNames(): array
    {
        return $this->tripid->getTravelerNames();
    }

    public function getServiceClasses(): array
    {
        return $this->serviceClasses;
    }

    /**
     * @param string[] $serviceClasses
     */
    public function setServiceClasses(array $serviceClasses): void
    {
        $this->serviceClasses = $serviceClasses;
    }

    public function getSourceKind(): string
    {
        return $this->sourceKind;
    }

    public function setSourceKind(?string $sourceKind): void
    {
        $this->sourceKind = $sourceKind;
    }

    public function getSourceId(): ?string
    {
        return $this->sourceId;
    }

    public function setSourceId(?string $sourceId): void
    {
        $this->sourceId = $sourceId;
    }

    public function isPartiallyUpdated()
    {
        return 'E' === $this->sourceKind;
    }

    public function getOperatingAirlineConfirmationNumber(): ?string
    {
        return $this->operatingAirlineConfirmationNumber;
    }

    public function setOperatingAirlineConfirmationNumber(?string $operatingAirlineConfirmationNumber): void
    {
        $this->operatingAirlineConfirmationNumber = $operatingAirlineConfirmationNumber;
    }

    public function getMarketingAirlineConfirmationNumber(): ?string
    {
        return $this->marketingAirlineConfirmationNumber;
    }

    public function getMarketingAirline(): ?Airline
    {
        return $this->airline;
    }

    public function getMarketingAirlineName(): ?string
    {
        return $this->airlineName;
    }

    public function getMarketingFlightNumber(): ?string
    {
        return $this->flightNumber;
    }

    /**
     * @throws \RuntimeException if Trip record locator becomes ambiguous
     */
    public function setMarketingAirlineConfirmationNumber(?string $marketingAirlineConfirmationNumber)
    {
        $this->marketingAirlineConfirmationNumber = $marketingAirlineConfirmationNumber;

        return $this;
    }

    /**
     * @return string[]
     */
    public function getOperatingAirlinePhoneNumbers(): ?array
    {
        return $this->operatingAirlinePhoneNumbers;
    }

    /**
     * @param string[] $operatingAirlinePhoneNumbers
     */
    public function setOperatingAirlinePhoneNumbers(array $operatingAirlinePhoneNumbers): void
    {
        $this->operatingAirlinePhoneNumbers = $operatingAirlinePhoneNumbers;
    }

    /**
     * @return string[]
     */
    public function getMarketingAirlinePhoneNumbers(): ?array
    {
        return $this->marketingAirlinePhoneNumbers;
    }

    /**
     * @param string[] $marketingAirlinePhoneNumbers
     */
    public function setMarketingAirlinePhoneNumbers(array $marketingAirlinePhoneNumbers): void
    {
        $this->marketingAirlinePhoneNumbers = $marketingAirlinePhoneNumbers;
    }

    public function getAdultsCount(): ?int
    {
        return $this->adultsCount;
    }

    public function setAdultsCount(?int $adultsCount): void
    {
        $this->adultsCount = $adultsCount;
    }

    public function getKidsCount(): ?int
    {
        return $this->kidsCount;
    }

    public function setKidsCount(?int $kidsCount): void
    {
        $this->kidsCount = $kidsCount;
    }

    public function getPets(): ?string
    {
        return $this->pets;
    }

    public function setPets(?string $pets): self
    {
        $this->pets = $pets;

        return $this;
    }

    /**
     * @return Vehicle[]|null
     */
    public function getVehicles(): ?array
    {
        return $this->vehicles;
    }

    /**
     * @param Vehicle[]|null $vehicles
     */
    public function setVehicles(?array $vehicles): self
    {
        $this->vehicles = $vehicles;

        return $this;
    }

    /**
     * @return Vehicle[]|null
     */
    public function getTrailers(): ?array
    {
        return $this->trailers;
    }

    /**
     * @param Vehicle[]|null $trailers
     */
    public function setTrailers(?array $trailers): self
    {
        $this->trailers = $trailers;

        return $this;
    }

    public function getServiceName(): ?string
    {
        return $this->serviceName;
    }

    public function setServiceName(?string $serviceName): void
    {
        $this->serviceName = $serviceName;
    }

    public function getCarNumber(): ?string
    {
        return $this->carNumber;
    }

    public function setCarNumber(?string $carNumber): void
    {
        $this->carNumber = $carNumber;
    }

    public function getWetLeaseAirline(): ?Airline
    {
        return $this->wetLeaseAirline;
    }

    public function setWetLeaseAirline(?Airline $wetLeaseAirline): void
    {
        $this->wetLeaseAirlineName = null !== $wetLeaseAirline ? $wetLeaseAirline->getName() : null;
        $this->wetLeaseAirline = $wetLeaseAirline;
    }

    public function getWetLeaseAirlineName(): ?string
    {
        return $this->wetLeaseAirlineName;
    }

    public function setWetLeaseAirlineName(?string $wetLeaseAirlineName): void
    {
        if (null !== $this->wetLeaseAirline && $this->wetLeaseAirline->getName() !== $wetLeaseAirlineName) {
            $this->airline = null;
        }
        $this->wetLeaseAirlineName = $wetLeaseAirlineName;
    }

    /**
     * @return string[]
     */
    public function getAllConfirmationNumbers(): array
    {
        // array_filter without callback will filter out empty values
        return array_filter(
            array_merge(
                [
                    $this->getMarketingAirlineConfirmationNumber(),
                    $this->getOperatingAirlineConfirmationNumber(),
                ],
                $this->tripid->getAllConfirmationNumbers()
            )
        );
    }

    public function getTripAlertsUpdateDate(): ?\DateTime
    {
        return $this->tripAlertsUpdateDate;
    }

    public function setTripAlertsUpdateDate(\DateTime $tripAlertsUpdateDate): void
    {
        $this->tripAlertsUpdateDate = $tripAlertsUpdateDate;
    }

    public function cancel(): void
    {
        $this->hideByUpdater();
    }

    public function hideByUser()
    {
        $this->hidden = self::HIDDEN_BY_USER;
    }

    public function hideByUpdater()
    {
        $this->hidden = self::HIDDEN_BY_UPDATER;
    }

    public function unhide()
    {
        $this->hidden = self::NOT_HIDDEN;
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

    public function getConfirmationNumber(): ?string
    {
        return $this->marketingAirlineConfirmationNumber ?? $this->operatingAirlineConfirmationNumber;
    }

    public function getType(): string
    {
        return $this->tripid->getType();
    }

    public function getParsedStatus(): ?string
    {
        return $this->parsedStatus ?? $this->tripid->getParsedStatus();
    }

    public function setParsedStatus(?string $parsedStatus): void
    {
        $this->parsedStatus = $parsedStatus;
    }

    public function isHiddenByUser(): bool
    {
        return $this->hidden === self::HIDDEN_BY_USER;
    }

    public function isHiddenByUpdater(): bool
    {
        return $this->hidden === self::HIDDEN_BY_UPDATER;
    }

    /**
     * @param bool $withCode
     * @return string
     */
    private function getAirportName($prefix, $withCode = true)
    {
        $codeField = $prefix . "code";
        $tagField = $prefix . "geotagid";
        $nameField = $prefix . "name";

        $parts = $withCode ? [$this->$codeField] : [];

        if (!empty($this->$tagField)) {
            $parts[] = $this->$tagField->getCity();

            if ($this->$tagField->getCountryCode() == 'US') {
                // us format: New York, NY
                $state = $this->$tagField->getState(true);

                if ($this->$tagField->getCity() != $state && !is_numeric($state)) {
                    $parts[] = $state;
                }

                if (empty($state)) {
                    $parts[] = $this->$tagField->getCountry();
                }
            } else {
                // overseas format: Vienna, Austria
                $country = $this->$tagField->getCountry();

                if (!empty($country)) {
                    $parts[] = $country;
                }
            }
        }

        $parts = array_filter($parts, function ($val) {
            return !empty($val);
        });

        if (count($parts) <= 1) {
            $parts[] = $this->$nameField;
            $parts = array_unique($parts);
        }

        return implode(", ", $parts);
    }
}
