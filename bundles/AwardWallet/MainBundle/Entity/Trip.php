<?php

namespace AwardWallet\MainBundle\Entity;

use AwardWallet\Common\DateTimeUtils;
use AwardWallet\Common\Entity\Geotag;
use AwardWallet\MainBundle\Timeline\QueryOptions;
use AwardWallet\MainBundle\Timeline\SegmentSourceInterface;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\Common\Collections\Criteria;
use Doctrine\ORM\Mapping as ORM;
use Doctrine\ORM\PersistentCollection;
use JMS\Serializer\Annotation as JMS;
use JMS\TranslationBundle\Model\Message;

/**
 * Trip.
 *
 * @ORM\Table(name="Trip")
 * @ORM\EntityListeners({ "AwardWallet\MainBundle\Entity\Listener\ItineraryListener" })
 * @ORM\Entity(repositoryClass="AwardWallet\MainBundle\Entity\Repositories\TripRepository")
 * @ORM\HasLifecycleCallbacks()
 * @ORM\AttributeOverrides({
 *      @ORM\AttributeOverride(name="id",
 *          column=@ORM\Column(name = "TripID", type="integer", nullable=false)
 *      ),
 *      @ORM\AttributeOverride(name="confirmationNumber",
 *          column=@ORM\Column(name = "RecordLocator", type="string", length=100, nullable=false)
 *      ),
 * })
 * @JMS\ExclusionPolicy("all")
 */
class Trip extends Itinerary implements SegmentSourceInterface
{
    public const CATEGORY_AIR = 1;
    public const CATEGORY_BUS = 2;
    public const CATEGORY_TRAIN = 3;
    public const CATEGORY_CRUISE = 4;
    public const CATEGORY_FERRY = 5;
    public const CATEGORY_TRANSFER = 6;

    public const CATEGORY_NAMES = [
        self::CATEGORY_AIR => 'Air',
        self::CATEGORY_BUS => 'Bus',
        self::CATEGORY_TRAIN => 'Train',
        self::CATEGORY_CRUISE => 'Cruise',
        self::CATEGORY_FERRY => 'Ferry',
        self::CATEGORY_TRANSFER => 'Transfer',
    ];

    public const SEGMENT_MAP = 'T';

    /**
     * @var string|null
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
     * @var int
     * @ORM\Column(name="Category", type="integer", nullable=false)
     */
    protected $category = self::CATEGORY_AIR;

    /**
     * @var bool
     * @ORM\Column(name="Direction", type="boolean", nullable=false)
     */
    protected $direction = false;

    /**
     * @var Tripsegment[]|PersistentCollection
     * @ORM\OneToMany(targetEntity="Tripsegment", mappedBy="tripid", cascade={"persist", "remove"}, orphanRemoval=true)
     **/
    protected $segments;

    /**
     * @var string|null
     * @ORM\Column(type="string")
     */
    private $cruiseName;

    /**
     * @var string|null
     * @ORM\Column(type="string")
     */
    private $deck;

    /**
     * @var string|null
     * @ORM\Column(type="string", name="CabinClass")
     */
    private $shipCabinClass;

    /**
     * @var string|null
     * @ORM\Column(type="string")
     */
    private $cabinNumber;

    /**
     * @var string|null
     * @ORM\Column(type="string")
     */
    private $shipCode;

    /**
     * @var string|null
     * @ORM\Column(type="string")
     */
    private $shipName;

    /**
     * @var TicketNumber[]
     * @ORM\Column(name="TicketNumbers", type="simple_array")
     * @JMS\Expose()
     * @JMS\Groups({"details"})
     * @JMS\Type("array<string>")
     */
    private $ticketNumbers = [];

    /**
     * @var string|null
     * @ORM\Column(name="IssuingAirlineConfirmationNumber", type="string")
     */
    private $issuingAirlineConfirmationNumber;

    public function __construct()
    {
        parent::__construct();
        $this->segments = new ArrayCollection();
    }

    public function __clone()
    {
        parent::__clone();
        $segments = $this->getSegments();
        $this->segments = new ArrayCollection();

        if ($segments) {
            /** @var Tripsegment $seg */
            foreach ($segments as $seg) {
                $clonedSegment = clone $seg;
                $this->segments->add($clonedSegment);
                $clonedSegment->setTripid($this);
            }
        }
    }

    /**
     * Set category.
     *
     * @param int $category
     * @return Trip
     */
    public function setCategory($category)
    {
        $this->category = $category;

        return $this;
    }

    /**
     * Get category.
     *
     * @return int
     */
    public function getCategory()
    {
        return $this->category;
    }

    /**
     * Set direction.
     *
     * @param bool $direction
     * @return Trip
     */
    public function setDirection($direction)
    {
        $this->direction = $direction;

        return $this;
    }

    /**
     * Get direction.
     *
     * @return bool
     */
    public function getDirection()
    {
        return $this->direction;
    }

    public function getKind(): string
    {
        return Itinerary::KIND_TRIP;
    }

    public function getTimelineItems(Usr $user, ?QueryOptions $queryOptions = null): array
    {
        $result = [];

        foreach ($this->segments as $segment) {
            $result = array_merge($result, $segment->getTimelineItems($user, $queryOptions));
        }

        return $result;
    }

    /**
     * @return Tripsegment[]|PersistentCollection
     */
    public function getSegments()
    {
        return $this->segments;
    }

    /**
     * @return Collection|Tripsegment[]
     */
    public function getVisibleSegments()
    {
        $criteria = Criteria::create()->where(Criteria::expr()->eq('hidden', false))->orderBy(['depdate' => 'ASC']);
        $segments = $this->segments->matching($criteria)->toArray();

        return $segments;
    }

    /**
     * @return Tripsegment[]
     */
    public function getSegmentsSorted()
    {
        $segments = $this->segments->toArray();
        $segments = array_filter($segments, function (Tripsegment $tripsegment) {
            return !$tripsegment->getHidden();
        });
        @usort(
            $segments,
            function (Tripsegment $a, Tripsegment $b) {
                $d1 = Geotag::getLocalDateTimeByGeoTag($a->getDepdate(), $a->getDepgeotagid());
                $d2 = Geotag::getLocalDateTimeByGeoTag($b->getDepdate(), $b->getDepgeotagid());

                return $d1 < $d2 ? -1 : ($d1 > $d2 ? 1 : 0);
            }
        );

        return $segments;
    }

    public static function getSegmentMap()
    {
        return [self::SEGMENT_MAP];
    }

    /**
     * @return string[]
     */
    public function getPhones()
    {
        if (null !== $this->phone) {
            return [$this->phone];
        } else {
            return [];
        }
    }

    /**
     * @return Geotag[]
     */
    public function getGeoTags()
    {
        $result = [];

        foreach ($this->segments as $segment) {
            foreach ([$segment->getDepgeotagid(), $segment->getArrgeotagid()] as $tag) {
                if (!empty($tag)) {
                    $result[] = $tag;
                }
            }
        }

        return $result;
    }

    /**
     * @return $this
     */
    public function addSegment(Tripsegment $segment)
    {
        if (!$this->segments->contains($segment)) {
            $this->segments->add($segment);
            $segment->setTripid($this);
        }

        return $this;
    }

    public function getStartDate()
    {
        return $this->segments[0]->getStartDate();
    }

    public function getEndDate()
    {
        return $this->segments[count($this->segments) - 1]->getEndDate();
    }

    public function getUTCStartDate()
    {
        return $this->segments[0]->getUTCStartDate();
    }

    public function getUTCEndDate()
    {
        return $this->segments[count($this->segments) - 1]->getUTCEndDate();
    }

    /**
     * Returns an array of messages.
     *
     * @return array<Message>
     */
    public static function getTranslationMessages()
    {
        return array_merge(parent::getTranslationMessages(), [
            (new Message("segments.at-least-one", "validators"))->setDesc("At least one trip segment required."),
        ]);
    }

    public function getCruiseName(): ?string
    {
        return $this->cruiseName;
    }

    public function setCruiseName(?string $cruiseName): void
    {
        $this->cruiseName = $cruiseName;
    }

    public function getDeck(): ?string
    {
        return $this->deck;
    }

    public function setDeck(?string $deck): void
    {
        $this->deck = $deck;
    }

    public function getShipCabinClass(): ?string
    {
        return $this->shipCabinClass;
    }

    public function setShipCabinClass(?string $shipCabinClass): void
    {
        $this->shipCabinClass = $shipCabinClass;
    }

    public function getCabinNumber(): ?string
    {
        return $this->cabinNumber;
    }

    public function setCabinNumber(?string $cabinNumber): void
    {
        $this->cabinNumber = $cabinNumber;
    }

    public function getShipCode(): ?string
    {
        return $this->shipCode;
    }

    public function setShipCode(?string $shipCode): void
    {
        $this->shipCode = $shipCode;
    }

    public function getShipName(): ?string
    {
        return $this->shipName;
    }

    public function setShipName(?string $shipName): void
    {
        $this->shipName = $shipName;
    }

    /**
     * @return TicketNumber[]
     */
    public function getTicketNumbers(): array
    {
        return $this->ticketNumbers;
    }

    /**
     * @param TicketNumber[] $ticketNumbers
     */
    public function setTicketNumbers(array $ticketNumbers): void
    {
        $this->ticketNumbers = $ticketNumbers;
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
     * If only the airline name is needed, then @see getAirlineName().
     */
    public function getAirline(): ?Airline
    {
        return $this->airline;
    }

    public function getIssuingAirline(): ?Airline
    {
        return $this->airline;
    }

    public function getIssuingAirlineName(): ?string
    {
        return $this->airlineName;
    }

    public function setAirline(?Airline $airline): void
    {
        if (null !== $airline) {
            $this->airlineName = $airline->getName();
        } else {
            $this->airlineName = null;
        }
        $this->airline = $airline;
    }

    /**
     * @return string flight, cruise, reservation, rental, etc
     */
    public function getType(): string
    {
        switch ($this->category) {
            case self::CATEGORY_AIR:
                return 'flight';

            case self::CATEGORY_BUS:
                return 'bus_ride';

            case self::CATEGORY_TRAIN:
                return 'train_ride';

            case self::CATEGORY_CRUISE:
                return 'cruise';

            case self::CATEGORY_FERRY:
                return 'ferry_ride';

            case self::CATEGORY_TRANSFER:
                return 'transfer';

            default:
                throw new \LogicException("Unknown category!");
        }
    }

    /**
     * Get the total number of trip days.
     */
    public static function getDaysCount(\DateTime $startDate, \DateTime $endDate): int
    {
        return max(
            (strtotime($endDate->format('Y-m-d')) - strtotime($startDate->format('Y-m-d'))) / DateTimeUtils::SECONDS_PER_DAY,
            1
        );
    }

    /**
     * @ORM\PreFlush()
     */
    public function updateRecordLocator(): void
    {
        if ($this->category !== self::CATEGORY_AIR) {
            return;
        }

        $marketingAirlineConfirmationNumbers =
            array_unique(
                array_filter(
                    array_map(
                        function (Tripsegment $segment) {
                            return $segment->getMarketingAirlineConfirmationNumber();
                        }, $this->segments->toArray()
                    )
                )
            );
        $marketingAirlineConfirmationNumber = 1 === count($marketingAirlineConfirmationNumbers)
            ? reset($marketingAirlineConfirmationNumbers)
            : null;

        $travelAgencyConfirmationNumber = !empty($this->travelAgencyConfirmationNumbers)
            ? reset($this->travelAgencyConfirmationNumbers)
            : null;

        $this->confirmationNumber = $marketingAirlineConfirmationNumber
            ?? $travelAgencyConfirmationNumber
            ?? $this->issuingAirlineConfirmationNumber;
    }

    /**
     * @throws \RuntimeException if record locator becomes ambiguous
     */
    public function setTravelAgencyConfirmationNumbers(array $numbers)
    {
        parent::setTravelAgencyConfirmationNumbers($numbers);
    }

    public function getIssuingAirlineConfirmationNumber(): ?string
    {
        return $this->issuingAirlineConfirmationNumber;
    }

    public function setIssuingAirlineConfirmationNumber(?string $issuingAirlineConfirmationNumber): Trip
    {
        $this->issuingAirlineConfirmationNumber = $issuingAirlineConfirmationNumber;

        return $this;
    }

    public function setConfirmationNumber(?string $confirmationNumber): void
    {
        if (self::CATEGORY_AIR === $this->category) {
            foreach ($this->segments as $segment) {
                $segment->setMarketingAirlineConfirmationNumber($confirmationNumber);
            }

            if (null === $confirmationNumber) {
                $this->travelAgencyConfirmationNumbers = [];
            }
        } else {
            parent::setConfirmationNumber($confirmationNumber);
        }
    }

    public function isHiddenByUser(): bool
    {
        foreach ($this->segments as $segment) {
            /** @var Tripsegment $segment */
            if ($segment->isHiddenByUser()) {
                return true;
            }
        }

        return false;
    }
}
