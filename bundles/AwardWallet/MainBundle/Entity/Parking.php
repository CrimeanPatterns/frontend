<?php

namespace AwardWallet\MainBundle\Entity;

use AwardWallet\Common\DateTimeUtils;
use AwardWallet\Common\Entity\Geotag;
use AwardWallet\MainBundle\Loyalty\AccountSaving\Sources\SourceListInterface;
use AwardWallet\MainBundle\Timeline\Item;
use AwardWallet\MainBundle\Timeline\QueryOptions;
use AwardWallet\MainBundle\Timeline\SegmentSourceInterface;
use Doctrine\ORM\Mapping as ORM;

/**
 * Parking.
 *
 * @ORM\Table(name="Parking")
 * @ORM\EntityListeners({ "AwardWallet\MainBundle\Entity\Listener\ItineraryListener" })
 * @ORM\Entity(repositoryClass="AwardWallet\MainBundle\Entity\Repositories\ParkingRepository")
 * @ORM\AttributeOverrides({
 *      @ORM\AttributeOverride(name="id",
 *          column=@ORM\Column(name = "ParkingID", type="integer", nullable=false)
 *      ),
 *      @ORM\AttributeOverride(name="confirmationNumber",
 *          column=@ORM\Column(name = "Number", type="string", length=100, nullable=false)
 *      ),
 * })
 */
class Parking extends Itinerary implements SegmentSourceInterface, SourceListInterface, ShowAIWarningForEmailSourceInterface
{
    use SourceTrait;
    use AIWarningTrait;

    public const SEGMENT_MAP_START = 'PS';
    public const SEGMENT_MAP_END = 'PE';

    /**
     * @var GeoTag
     * @ORM\ManyToOne(targetEntity="\AwardWallet\Common\Entity\Geotag")
     * @ORM\JoinColumns({
     *   @ORM\JoinColumn(name="GeoTagID", referencedColumnName="GeoTagID")
     * })
     */
    protected $geotagid;

    /**
     * @var \DateTime
     * @ORM\Column(name="StartDatetime", type="datetime", nullable=false, options={"comment"="Дата и время начала парковки"})
     */
    protected $startdatetime;

    /**
     * @var \DateTime
     * @ORM\Column(name="EndDatetime", type="datetime", nullable=false, options={"comment"="Дата и время окончания парковки"})
     */
    protected $enddatetime;

    /**
     * @var string|null
     * @ORM\Column(name="Phone", type="string", length=30, nullable=true, options={"comment"="Номер телефона"})
     */
    protected $phone;
    /**
     * @var string|null
     * @ORM\Column(name="ParkingCompanyName", type="string", length=80, nullable=true, options={"comment"="Название компании"})
     */
    private $parkingcompanyname;

    /**
     * @var string|null
     * @ORM\Column(name="Location", type="string", length=160, nullable=true, options={"comment"="Название места для парковки"})
     */
    private $location;

    /**
     * @var string|null
     * @ORM\Column(name="Spot", type="string", length=30, nullable=true, options={"comment"="Номер места на парковке"})
     */
    private $spot;

    /**
     * @var string|null
     * @ORM\Column(name="Plate", type="string", length=20, nullable=true, options={"comment"="Номер машины"})
     */
    private $plate;

    /**
     * @var string|null
     * @ORM\Column(name="CarDescription", type="string", length=250, nullable=true, options={"comment"="Описание машины"})
     */
    private $cardescription;

    /**
     * @var string|null
     * @ORM\Column(name="RateType", type="string", length=250, nullable=true)
     */
    private $rateType;

    /**
     * @var \DateTime|null
     * @ORM\Column(name="ChangeDate", type="datetime", nullable=true, options={"comment"="Дата последнего изменения одного из свойств на сайте провайдера"})
     */
    private $changedate;

    /**
     * Get ParkingCompanyName.
     */
    public function getParkingCompanyName(): ?string
    {
        return $this->parkingcompanyname;
    }

    /**
     * Set ParkingCompanyName.
     */
    public function setParkingCompanyName(?string $parkingCompanyName): self
    {
        $this->parkingcompanyname = $parkingCompanyName;

        return $this;
    }

    /**
     * Get location.
     */
    public function getLocation(): ?string
    {
        if ($this->geotagid !== null) {
            return $this->geotagid->getAddress();
        }

        return $this->location;
    }

    /**
     * Set location.
     */
    public function setLocation(?string $location): self
    {
        $this->location = $location;

        return $this;
    }

    /**
     * Get CeotagId.
     *
     * @return Geotag
     */
    public function getGeoTagID()
    {
        return $this->geotagid;
    }

    /**
     * Set Geotagid.
     */
    public function setGeoTagID(?Geotag $geoTagID): self
    {
        if ($geoTagID !== null) {
            $this->location = $geoTagID->getAddress();
        } else {
            $this->location = null;
        }
        $this->geotagid = $geoTagID;

        return $this;
    }

    /**
     * Get spot number.
     *
     * @return string|null
     */
    public function getSpot()
    {
        return $this->spot;
    }

    /**
     * Set spot number.
     */
    public function setSpot($spot): self
    {
        $this->spot = $spot;

        return $this;
    }

    /**
     * Get start datetime.
     *
     * @return \DateTime
     */
    public function getStartDatetime()
    {
        return $this->startdatetime;
    }

    /**
     * Set start datetime.
     */
    public function setStartDatetime($startDatetime): self
    {
        $this->startdatetime = $startDatetime;

        return $this;
    }

    /**
     * Get end datetime.
     *
     * @return \DateTime
     */
    public function getEndDatetime()
    {
        return $this->enddatetime;
    }

    /**
     * Set end date time.
     */
    public function setEndDatetime($endDatetime): self
    {
        $this->enddatetime = $endDatetime;

        return $this;
    }

    /**
     * Set license plate.
     *
     * @return string|null
     */
    public function getPlate()
    {
        return $this->plate;
    }

    /**
     * Set license plate.
     */
    public function setPlate($plate): self
    {
        $this->plate = $plate;

        return $this;
    }

    /**
     * Set car description.
     *
     * @return string|null
     */
    public function getCarDescription()
    {
        return $this->cardescription;
    }

    /**
     * Set car description.
     */
    public function setCarDescription($carDescription): self
    {
        $this->cardescription = $carDescription;

        return $this;
    }

    public function getRateType(): ?string
    {
        return $this->rateType;
    }

    public function setRateType(?string $rateType): self
    {
        $this->rateType = $rateType;

        return $this;
    }

    public function getChangedate(): ?\DateTime
    {
        return $this->changedate;
    }

    public function setChangedate(?\DateTime $changedate): self
    {
        $this->changedate = $changedate;

        return $this;
    }

    public function getTimelineItems(Usr $user, ?QueryOptions $queryOptions = null): array
    {
        if (empty($this->geotagid) && !empty($this->location)) {
            $tag = FindGeoTag($this->location);
            $this->geotagid = $queryOptions->getGeotags()->find($tag['GeoTagID']);
        }

        return $this->getParkingTimelineItems();
    }

    public function getKind(): string
    {
        return Itinerary::KIND_PARKING;
    }

    public static function getSegmentMap()
    {
        return [self::SEGMENT_MAP_START, self::SEGMENT_MAP_END];
    }

    public function getPhones()
    {
        if ($this->getPhone() !== null) {
            return [$this->getPhone()];
        }

        return [];
    }

    public function setRealProvider(?Provider $provider = null)
    {
        parent::setRealProvider($provider);

        if (null !== $provider) {
            $this->parkingcompanyname = $provider->getShortname();
        }
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
        return $this->startdatetime;
    }

    public function getEndDate()
    {
        return $this->enddatetime;
    }

    public function getUTCStartDate()
    {
        return Geotag::getLocalDateTimeByGeoTag($this->startdatetime, $this->geotagid);
    }

    public function getUTCEndDate()
    {
        return Geotag::getLocalDateTimeByGeoTag($this->enddatetime, $this->geotagid);
    }

    /**
     * @return string flight, reservation, rental, etc
     */
    public function getType(): string
    {
        return 'parking';
    }

    public function getDays()
    {
        return self::getDayCount($this->startdatetime, $this->enddatetime);
    }

    public static function getDayCount(\DateTime $startDate, \DateTime $endDate): int
    {
        return max(
            (strtotime($endDate->format('Y-m-d')) - strtotime($startDate->format('Y-m-d'))) / DateTimeUtils::SECONDS_PER_DAY,
            1
        );
    }

    private function getParkingTimelineItems(): array
    {
        $startSeg = new Item\ParkingStart($this);
        $endSeg = new Item\ParkingEnd($this);

        $startSeg->setConnection($endSeg);
        $endSeg->setConnection($startSeg);

        return [$startSeg, $endSeg];
    }
}
