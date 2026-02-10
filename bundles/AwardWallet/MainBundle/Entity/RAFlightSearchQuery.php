<?php

namespace AwardWallet\MainBundle\Entity;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Table(name="RAFlightSearchQuery")
 * @ORM\Entity
 */
class RAFlightSearchQuery
{
    public const FLIGHT_CLASS_ECONOMY = 1 << 0;
    public const FLIGHT_CLASS_PREMIUM_ECONOMY = 1 << 1;
    public const FLIGHT_CLASS_BUSINESS = 1 << 2;
    public const FLIGHT_CLASS_FIRST = 1 << 3;

    public const API_FLIGHT_CLASS_ECONOMY = 'economy';
    public const API_FLIGHT_CLASS_PREMIUM_ECONOMY = 'premiumEconomy';
    public const API_FLIGHT_CLASS_BUSINESS = 'business';
    public const API_FLIGHT_CLASS_FIRST = 'firstClass';

    public const API_FLIGHT_CLASSES = [
        self::API_FLIGHT_CLASS_ECONOMY,
        self::API_FLIGHT_CLASS_PREMIUM_ECONOMY,
        self::API_FLIGHT_CLASS_BUSINESS,
        self::API_FLIGHT_CLASS_FIRST,
    ];

    public const SEARCH_INTERVAL_ONCE = 1;
    public const SEARCH_INTERVAL_DAILY = 2;
    public const SEARCH_INTERVAL_WEEKLY = 3;

    /**
     * @var int
     * @ORM\Column(name="RAFlightSearchQueryID", type="integer", nullable=false)
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="IDENTITY")
     */
    private $id;

    /**
     * @var Usr
     * @ORM\ManyToOne(targetEntity="AwardWallet\MainBundle\Entity\Usr")
     * @ORM\JoinColumns({
     *   @ORM\JoinColumn(name="UserID", referencedColumnName="UserID")
     * })
     */
    private $user;

    /**
     * @var RAFlightSearchRoute[]
     * @ORM\OneToMany(targetEntity="AwardWallet\MainBundle\Entity\RAFlightSearchRoute", mappedBy="query", cascade={"persist", "remove"})
     */
    private $routes;

    /**
     * @var array
     * @ORM\Column(name="DepartureAirports", type="json", nullable=false)
     */
    private $departureAirports;

    /**
     * @var \DateTime
     * @ORM\Column(name="DepDateFrom", type="date", nullable=false)
     */
    private $depDateFrom;

    /**
     * @var \DateTime
     * @ORM\Column(name="DepDateTo", type="date", nullable=false)
     */
    private $depDateTo;

    /**
     * @var array
     * @ORM\Column(name="ArrivalAirports", type="json", nullable=false)
     */
    private $arrivalAirports;

    /**
     * @var int
     * @ORM\Column(name="FlightClass", type="smallint", nullable=false)
     */
    private $flightClass;

    /**
     * @var int
     * @ORM\Column(name="Adults", type="smallint", nullable=false)
     */
    private $adults = 1;

    /**
     * @var int
     * @ORM\Column(name="SearchInterval", type="smallint", nullable=false)
     */
    private $searchInterval = self::SEARCH_INTERVAL_ONCE;

    /**
     * @var string
     * @ORM\Column(name="Parsers", type="string", nullable=true)
     */
    private $parsers;

    /**
     * @var bool
     * @ORM\Column(name="AutoSelectParsers", type="boolean", nullable=false)
     */
    private $autoSelectParsers = false;

    /**
     * @var string
     * @ORM\Column(name="ExcludeParsers", type="string", nullable=true)
     */
    private $excludeParsers;

    /**
     * @var string|null
     * @ORM\Column(name="LastSearchKey", type="string", nullable=true)
     */
    private $lastSearchKey;

    /**
     * @var int|null
     * @ORM\Column(name="EconomyMilesLimit", type="integer", nullable=true)
     */
    private $economyMilesLimit;

    /**
     * @var int|null
     * @ORM\Column(name="PremiumEconomyMilesLimit", type="integer", nullable=true)
     */
    private $premiumEconomyMilesLimit;

    /**
     * @var int|null
     * @ORM\Column(name="BusinessMilesLimit", type="integer", nullable=true)
     */
    private $businessMilesLimit;

    /**
     * @var int|null
     * @ORM\Column(name="FirstMilesLimit", type="integer", nullable=true)
     */
    private $firstMilesLimit;

    /**
     * @var float|null
     * @ORM\Column(name="MaxTotalDuration", type="decimal", precision=3, scale=2, nullable=true)
     */
    private $maxTotalDuration;

    /**
     * @var float|null
     * @ORM\Column(name="MaxSingleLayoverDuration", type="decimal", precision=3, scale=2, nullable=true)
     */
    private $maxSingleLayoverDuration;

    /**
     * @var float|null
     * @ORM\Column(name="MaxTotalLayoverDuration", type="decimal", precision=3, scale=2, nullable=true)
     */
    private $maxTotalLayoverDuration;

    /**
     * @var int|null
     * @ORM\Column(name="MaxStops", type="integer", nullable=true)
     */
    private $maxStops;

    /**
     * @var int
     * @ORM\Column(name="SubSearchCount", type="integer", nullable=false)
     */
    private $subSearchCount = 0;

    /**
     * @var int
     * @ORM\Column(name="SearchCount", type="integer", nullable=false)
     */
    private $searchCount = 0;

    /**
     * @var \DateTime
     * @ORM\Column(name="CreateDate", type="datetime", nullable=false, options={"default"="CURRENT_TIMESTAMP"})
     */
    private $createDate;

    /**
     * @var \DateTime
     * @ORM\Column(name="UpdateDate", type="datetime", nullable=true)
     */
    private $updateDate;

    /**
     * @var \DateTime|null
     * @ORM\Column(name="LastSearchDate", type="datetime", nullable=true)
     */
    private $lastSearchDate;

    /**
     * @var array|null
     * @ORM\Column(name="State", type="json", nullable=true)
     */
    private $state;

    /**
     * @ORM\OneToOne(targetEntity="AwardWallet\MainBundle\Entity\MileValue")
     * @ORM\JoinColumns({
     *    @ORM\JoinColumn(name="MileValueID", referencedColumnName="MileValueID", unique=true)
     *  })
     */
    private $mileValue;

    /**
     * @var \DateTime|null
     * @ORM\Column(name="DeleteDate", type="datetime", nullable=true)
     */
    private $deleteDate;

    public function __construct()
    {
        $this->routes = new ArrayCollection();
        $this->createDate = new \DateTime();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getUser(): ?Usr
    {
        return $this->user;
    }

    public function setUser(?Usr $user): self
    {
        $this->user = $user;

        return $this;
    }

    /**
     * @return RAFlightSearchRoute[]|ArrayCollection
     */
    public function getRoutes()
    {
        return $this->routes;
    }

    public function addRoute(RAFlightSearchRoute $route): self
    {
        $route->setQuery($this);
        $this->routes->add($route);

        return $this;
    }

    public function removeRoute(RAFlightSearchRoute $route): self
    {
        $this->routes->removeElement($route);

        return $this;
    }

    public function getDepartureAirports(): ?array
    {
        return $this->departureAirports;
    }

    public function setDepartureAirports(array $departureAirports): self
    {
        $this->departureAirports = $departureAirports;

        return $this;
    }

    public function getDepDateFrom(): ?\DateTimeInterface
    {
        return $this->depDateFrom;
    }

    public function setDepDateFrom(\DateTimeInterface $depDateFrom): self
    {
        $this->depDateFrom = $depDateFrom;

        return $this;
    }

    public function getDepDateTo(): ?\DateTimeInterface
    {
        return $this->depDateTo;
    }

    public function setDepDateTo(\DateTimeInterface $depDateTo): self
    {
        $this->depDateTo = $depDateTo;

        return $this;
    }

    public function getArrivalAirports(): ?array
    {
        return $this->arrivalAirports;
    }

    public function setArrivalAirports(array $arrivalAirports): self
    {
        $this->arrivalAirports = $arrivalAirports;

        return $this;
    }

    public function getFlightClass(): ?int
    {
        return $this->flightClass;
    }

    public function setFlightClass(int $flightClass): self
    {
        $this->flightClass = $flightClass;

        return $this;
    }

    public function getAdults(): ?int
    {
        return $this->adults;
    }

    public function setAdults(int $adults): self
    {
        $this->adults = $adults;

        return $this;
    }

    public function getSearchInterval(): ?int
    {
        return $this->searchInterval;
    }

    public function setSearchInterval(int $searchInterval): self
    {
        $this->searchInterval = $searchInterval;

        return $this;
    }

    public function getParsers(): ?string
    {
        return $this->parsers;
    }

    public function getParsersAsArray(): array
    {
        if (!empty($this->parsers)) {
            return array_map('trim', explode(',', $this->parsers));
        }

        return [];
    }

    public function setParsers(?string $parsers): self
    {
        $this->parsers = $parsers;

        return $this;
    }

    public function setParsersFromArray(array $parsers): self
    {
        if (empty($parsers)) {
            $this->parsers = null;
        } else {
            $this->parsers = implode(', ', array_map('trim', $parsers));
        }

        return $this;
    }

    public function getAutoSelectParsers(): bool
    {
        return $this->autoSelectParsers;
    }

    public function setAutoSelectParsers(bool $autoSelectParsers): self
    {
        $this->autoSelectParsers = $autoSelectParsers;

        return $this;
    }

    public function getExcludeParsers(): ?string
    {
        return $this->excludeParsers;
    }

    public function getExcludeParsersAsArray(): array
    {
        if (!empty($this->excludeParsers)) {
            return array_map('trim', explode(',', $this->excludeParsers));
        }

        return [];
    }

    public function setExcludeParsers(?string $excludeParsers): self
    {
        $this->excludeParsers = $excludeParsers;

        return $this;
    }

    public function setExcludeParsersFromArray(array $excludeParsers): self
    {
        if (empty($excludeParsers)) {
            $this->excludeParsers = null;
        } else {
            $this->excludeParsers = implode(', ', array_map('trim', $excludeParsers));
        }

        return $this;
    }

    public function getLastSearchKey(): ?string
    {
        return $this->lastSearchKey;
    }

    public function setLastSearchKey(?string $lastSearchKey): self
    {
        $this->lastSearchKey = $lastSearchKey;

        return $this;
    }

    public function getEconomyMilesLimit(): ?int
    {
        return $this->economyMilesLimit;
    }

    public function setEconomyMilesLimit(?int $economyMilesLimit): self
    {
        $this->economyMilesLimit = $economyMilesLimit;

        return $this;
    }

    public function getPremiumEconomyMilesLimit(): ?int
    {
        return $this->premiumEconomyMilesLimit;
    }

    public function setPremiumEconomyMilesLimit(?int $premiumEconomyMilesLimit): self
    {
        $this->premiumEconomyMilesLimit = $premiumEconomyMilesLimit;

        return $this;
    }

    public function getBusinessMilesLimit(): ?int
    {
        return $this->businessMilesLimit;
    }

    public function setBusinessMilesLimit(?int $businessMilesLimit): self
    {
        $this->businessMilesLimit = $businessMilesLimit;

        return $this;
    }

    public function getFirstMilesLimit(): ?int
    {
        return $this->firstMilesLimit;
    }

    public function setFirstMilesLimit(?int $firstMilesLimit): self
    {
        $this->firstMilesLimit = $firstMilesLimit;

        return $this;
    }

    public function getMaxTotalDuration(): ?float
    {
        return $this->maxTotalDuration;
    }

    public function setMaxTotalDuration(?float $maxTotalDuration): self
    {
        $this->maxTotalDuration = $maxTotalDuration;

        return $this;
    }

    public function getMaxSingleLayoverDuration(): ?float
    {
        return $this->maxSingleLayoverDuration;
    }

    public function setMaxSingleLayoverDuration(?float $maxSingleLayoverDuration): self
    {
        $this->maxSingleLayoverDuration = $maxSingleLayoverDuration;

        return $this;
    }

    public function getMaxTotalLayoverDuration(): ?float
    {
        return $this->maxTotalLayoverDuration;
    }

    public function setMaxTotalLayoverDuration(?float $maxTotalLayoverDuration): self
    {
        $this->maxTotalLayoverDuration = $maxTotalLayoverDuration;

        return $this;
    }

    public function getMaxStops(): ?int
    {
        return $this->maxStops;
    }

    public function setMaxStops(?int $maxStops): self
    {
        $this->maxStops = $maxStops;

        return $this;
    }

    public function getSubSearchCount(): int
    {
        return $this->subSearchCount;
    }

    public function setSubSearchCount(int $subSearchCount): self
    {
        $this->subSearchCount = $subSearchCount;

        return $this;
    }

    public function getSearchCount(): int
    {
        return $this->searchCount;
    }

    public function setSearchCount(int $searchCount): self
    {
        $this->searchCount = $searchCount;

        return $this;
    }

    public function incrementSearchCount(): self
    {
        $this->searchCount++;

        return $this;
    }

    public function getCreateDate(): ?\DateTimeInterface
    {
        return $this->createDate;
    }

    public function setUpdateDate(?\DateTime $updateDate): self
    {
        $this->updateDate = $updateDate;

        return $this;
    }

    public function getUpdateDate(): ?\DateTime
    {
        return $this->updateDate;
    }

    public function getLastSearchDate(): ?\DateTimeInterface
    {
        return $this->lastSearchDate;
    }

    public function setLastSearchDate(?\DateTimeInterface $lastSearchDate): self
    {
        $this->lastSearchDate = $lastSearchDate;

        return $this;
    }

    public function getState(): ?array
    {
        return $this->state;
    }

    public function setState(?array $state): self
    {
        $this->state = $state;

        return $this;
    }

    public function getMileValue(): ?MileValue
    {
        return $this->mileValue;
    }

    public function isAutoCreated(): bool
    {
        return !empty($this->mileValue);
    }

    public function setMileValue(?MileValue $mileValue): self
    {
        $this->mileValue = $mileValue;

        return $this;
    }

    public function getDeleteDate(): ?\DateTimeInterface
    {
        return $this->deleteDate;
    }

    public function isDeleted(): bool
    {
        return !empty($this->deleteDate);
    }

    public function setDeleteDate(?\DateTimeInterface $deleteDate): self
    {
        $this->deleteDate = $deleteDate;

        return $this;
    }

    public function delete(): self
    {
        $this->deleteDate = new \DateTime();

        return $this;
    }
}
