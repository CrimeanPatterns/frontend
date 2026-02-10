<?php

namespace AwardWallet\MainBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * MileValue.
 *
 * @ORM\Table(name="MileValue", uniqueConstraints={@ORM\UniqueConstraint(name="akTrip", columns={"TripID"})}, indexes={@ORM\Index(name="fkProvider", columns={"ProviderID"}), @ORM\Index(name="Status", columns={"Status"})})
 * @ORM\Entity
 */
class MileValue
{
    public const CUSTOM_PICK_CHEAPEST = 0;
    public const CUSTOM_PICK_YOUR_AWARD = 1;
    public const CUSTOM_PICK_USER_INPUT = 2;

    public const CUSTOM_PICKS = [
        self::CUSTOM_PICK_CHEAPEST,
        self::CUSTOM_PICK_YOUR_AWARD,
        self::CUSTOM_PICK_USER_INPUT,
    ];

    /**
     * @var int
     * @ORM\Column(name="MileValueID", type="integer", nullable=false)
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="IDENTITY")
     */
    private $id;

    /**
     * @var string|null
     * @ORM\Column(name="MileAirlines", type="string", length=40, nullable=true)
     */
    private $mileAirlines;

    /**
     * @var string|null
     * @ORM\Column(name="CashAirlines", type="string", length=250, nullable=true)
     */
    private $cashAirlines;

    /**
     * @var string
     * @ORM\Column(name="Route", type="string", length=120, nullable=false)
     */
    private $route;

    /**
     * @var bool
     * @ORM\Column(name="International", type="boolean", nullable=false)
     */
    private $international;

    /**
     * @var string
     * @ORM\Column(name="MileRoute", type="string", length=250, nullable=false)
     */
    private $mileRoute;

    /**
     * @var string
     * @ORM\Column(name="CashRoute", type="string", length=250, nullable=false)
     */
    private $cashRoute;

    /**
     * @var string
     * @ORM\Column(name="BookingClasses", type="string", length=20, nullable=false)
     */
    private $bookingClasses;

    /**
     * @var string
     * @ORM\Column(name="CabinClass", type="string", length=40, nullable=false)
     */
    private $cabinClass;

    /**
     * @var string
     * @ORM\Column(name="ClassOfService", type="string", length=40, nullable=false)
     */
    private $classOfService;

    /**
     * @var \DateTime
     * @ORM\Column(name="DepDate", type="datetime", nullable=false)
     */
    private $depDate;

    /**
     * @var \DateTime|null
     * @ORM\Column(name="ReturnDate", type="datetime", nullable=true)
     */
    private $returnDate;

    /**
     * @var string
     * @ORM\Column(name="MileDuration", type="decimal", precision=4, scale=1, nullable=false)
     */
    private $mileDuration;

    /**
     * @var string
     * @ORM\Column(name="CashDuration", type="decimal", precision=4, scale=1, nullable=false)
     */
    private $cashDuration;

    /**
     * @var string
     * @ORM\Column(name="Hash", type="string", length=32, nullable=false)
     */
    private $hash;

    /**
     * @var \DateTime
     * @ORM\Column(name="CreateDate", type="datetime", nullable=false)
     */
    private $createDate;

    /**
     * @var \DateTime
     * @ORM\Column(name="UpdateDate", type="datetime", nullable=false)
     */
    private $updateDate;

    /**
     * @var float
     * @ORM\Column(name="TotalMilesSpent", type="decimal", precision=10, scale=2, nullable=false)
     */
    private $totalMilesSpent;

    /**
     * @var string
     * @ORM\Column(name="TotalTaxesSpent", type="decimal", precision=10, scale=2, nullable=false)
     */
    private $totalTaxesSpent;

    /**
     * @var string
     * @ORM\Column(name="AlternativeCost", type="decimal", precision=10, scale=2, nullable=false)
     */
    private $alternativeCost;

    /**
     * @var string
     * @ORM\Column(name="MileValue", type="decimal", precision=10, scale=2, nullable=false)
     */
    private $mileValue;

    /**
     * @var string|null
     * @ORM\Column(name="Note", type="string", length=500, nullable=true, options={"comment"="User-entered note"})
     */
    private $note;

    /**
     * @var string
     * @ORM\Column(name="RouteType", type="string", length=3, nullable=false, options={"default"="UNK","comment"="RT - roundtrip, OW - oneway, MC - multicity, see CalcMileValueCommand::ROUTE_ constants"})
     */
    private $routeType = 'UNK';

    /**
     * @var int
     * @ORM\Column(name="TravelersCount", type="integer", nullable=false, options={"default"="1"})
     */
    private $travelersCount = '1';

    /**
     * @var string
     * @ORM\Column(name="Status", type="string", length=1, nullable=false, options={"default"="N","fixed"=true,"comment"="see CalcMileValueCommand::STATUSES"})
     */
    private $status = 'N';

    /**
     * @var float|null
     * @ORM\Column(name="TotalSpentInLocalCurrency", type="decimal", precision=10, scale=2, nullable=true)
     */
    private $totalSpentInLocalCurrency;

    /**
     * @var string|null
     * @ORM\Column(name="LocalCurrency", type="string", length=3, nullable=true)
     */
    private $localCurrency;

    /**
     * @var Provider
     * @ORM\ManyToOne(targetEntity="Provider")
     * @ORM\JoinColumns({
     *   @ORM\JoinColumn(name="ProviderID", referencedColumnName="ProviderID", nullable=false)
     * })
     */
    private $provider;

    /**
     * @var Trip
     * @ORM\ManyToOne(targetEntity="Trip")
     * @ORM\JoinColumns({
     *   @ORM\JoinColumn(name="TripID", referencedColumnName="TripID", nullable=true)
     * })
     */
    private $trip;

    /**
     * @var RAFlightSearchQuery
     * @ORM\OneToOne(targetEntity="RAFlightSearchQuery", mappedBy="mileValue", cascade={"persist", "remove", "refresh"})
     */
    private $raFlightSearchQuery;

    public function getId(): int
    {
        return $this->id;
    }

    /**
     * @return $this
     */
    public function setProvider(Provider $provider): self
    {
        $this->provider = $provider;

        return $this;
    }

    public function getProvider(): Provider
    {
        return $this->provider;
    }

    /**
     * @return $this
     */
    public function setTrip(?Trip $trip): self
    {
        $this->trip = $trip;

        return $this;
    }

    public function getTrip(): ?Trip
    {
        return $this->trip;
    }

    /**
     * @return $this
     */
    public function setMileAirlines(?string $mileAirlines): self
    {
        $this->mileAirlines = $mileAirlines;

        return $this;
    }

    public function getMileAirlines(): ?string
    {
        return $this->mileAirlines;
    }

    /**
     * @return $this
     */
    public function setCashAirlines(?string $cashAirlines): self
    {
        $this->cashAirlines = $cashAirlines;

        return $this;
    }

    public function getCashAirlines(): ?string
    {
        return $this->cashAirlines;
    }

    /**
     * @return $this
     */
    public function setRoute(string $route): self
    {
        $this->route = $route;

        return $this;
    }

    public function getRoute(): string
    {
        return $this->route;
    }

    /**
     * @return $this
     */
    public function setInternational(int $international): self
    {
        $this->international = $international;

        return $this;
    }

    public function getInternational(): int
    {
        return $this->international;
    }

    /**
     * @return $this
     */
    public function setMileRoute(string $mileRoute): self
    {
        $this->mileRoute = $mileRoute;

        return $this;
    }

    public function getMileRoute(): string
    {
        return $this->mileRoute;
    }

    /**
     * @return $this
     */
    public function setCashRoute(string $cashRoute): self
    {
        $this->cashRoute = $cashRoute;

        return $this;
    }

    public function getCashRoute(): string
    {
        return $this->cashRoute;
    }

    /**
     * @return $this
     */
    public function setBookingClasses(string $bookingClasses): self
    {
        $this->bookingClasses = $bookingClasses;

        return $this;
    }

    public function getBookingClasses(): string
    {
        return $this->bookingClasses;
    }

    /**
     * @return $this
     */
    public function setCabinClass(string $cabinClass): self
    {
        $this->cabinClass = $cabinClass;

        return $this;
    }

    public function getCabinClass(): string
    {
        return $this->cabinClass;
    }

    /**
     * @return $this
     */
    public function setClassOfService(string $classOfService): self
    {
        $this->classOfService = $classOfService;

        return $this;
    }

    public function getClassOfService(): string
    {
        return $this->classOfService;
    }

    public function getDepDate(): \DateTime
    {
        return $this->depDate;
    }

    /**
     * @return $this
     */
    public function setDepDate(\DateTime $depDate): self
    {
        $this->depDate = $depDate;

        return $this;
    }

    public function getReturnDate(): ?\DateTime
    {
        return $this->returnDate;
    }

    /**
     * @return $this
     */
    public function setReturnDate(?\DateTime $returnDate): self
    {
        $this->returnDate = $returnDate;

        return $this;
    }

    public function getMileDuration(): string
    {
        return $this->mileDuration;
    }

    /**
     * @return $this
     */
    public function setMileDuration(string $mileDuration): self
    {
        $this->mileDuration = $mileDuration;

        return $this;
    }

    public function getCashDuration(): string
    {
        return $this->cashDuration;
    }

    /**
     * @return $this
     */
    public function setCashDuration(string $cashDuration): self
    {
        $this->cashDuration = $cashDuration;

        return $this;
    }

    public function getHash(): string
    {
        return $this->hash;
    }

    /**
     * @return $this
     */
    public function setHash(string $hash): self
    {
        $this->hash = $hash;

        return $this;
    }

    public function getCreateDate(): \DateTime
    {
        return $this->createDate;
    }

    /**
     * @return $this
     */
    public function setCreateDate(\DateTime $createDate): self
    {
        $this->createDate = $createDate;

        return $this;
    }

    public function getUpdateDate(): \DateTime
    {
        return $this->updateDate;
    }

    /**
     * @return $this
     */
    public function setUpdateDate(\DateTime $updateDate): self
    {
        $this->updateDate = $updateDate;

        return $this;
    }

    public function getTotalMilesSpent(): float
    {
        return $this->totalMilesSpent;
    }

    /**
     * @return $this
     */
    public function setTotalMilesSpent(float $totalMilesSpent): self
    {
        $this->totalMilesSpent = $totalMilesSpent;

        return $this;
    }

    public function getTotalTaxesSpent(): string
    {
        return $this->totalTaxesSpent;
    }

    /**
     * @return $this
     */
    public function setTotalTaxesSpent(string $totalTaxesSpent): self
    {
        $this->totalTaxesSpent = $totalTaxesSpent;

        return $this;
    }

    public function getAlternativeCost(): string
    {
        return $this->alternativeCost;
    }

    /**
     * @return $this
     */
    public function setAlternativeCost(string $alternativeCost): self
    {
        $this->alternativeCost = $alternativeCost;

        return $this;
    }

    public function getMileValue(): string
    {
        return $this->mileValue;
    }

    /**
     * @return $this
     */
    public function setMileValue(string $mileValue): self
    {
        $this->mileValue = $mileValue;

        return $this;
    }

    public function getNote(): ?string
    {
        return $this->note;
    }

    /**
     * @return $this
     */
    public function setNote(?string $note): self
    {
        $this->note = $note;

        return $this;
    }

    public function getRouteType(): string
    {
        return $this->routeType;
    }

    /**
     * @return $this
     */
    public function setRouteType(string $routeType): self
    {
        $this->routeType = $routeType;

        return $this;
    }

    public function getTravelersCount(): int
    {
        return $this->travelersCount;
    }

    /**
     * @return $this
     */
    public function setTravelersCount(int $travelersCount): self
    {
        $this->travelersCount = $travelersCount;

        return $this;
    }

    public function getStatus(): string
    {
        return $this->status;
    }

    /**
     * @return $this
     */
    public function setStatus(string $status): self
    {
        $this->status = $status;

        return $this;
    }

    public function getTotalSpentInLocalCurrency(): ?float
    {
        return $this->totalSpentInLocalCurrency;
    }

    public function setTotalSpentInLocalCurrency(?float $totalSpentInLocalCurrency): self
    {
        $this->totalSpentInLocalCurrency = $totalSpentInLocalCurrency;

        return $this;
    }

    public function getLocalCurrency(): ?string
    {
        return $this->localCurrency;
    }

    public function setLocalCurrency(?string $localCurrency): self
    {
        $this->localCurrency = $localCurrency;

        return $this;
    }

    public function getRaFlightSearchQuery(): ?RAFlightSearchQuery
    {
        return $this->raFlightSearchQuery;
    }

    public function setRaFlightSearchQuery(?RAFlightSearchQuery $raFlightSearchQuery): self
    {
        $this->raFlightSearchQuery = $raFlightSearchQuery;

        return $this;
    }
}
