<?php

namespace AwardWallet\MainBundle\Entity;

use AwardWallet\MainBundle\Service\RA\Flight\Duration;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Table(name="RAFlightSearchRoute")
 * @ORM\Entity
 */
class RAFlightSearchRoute
{
    /**
     * @var int
     * @ORM\Column(name="RAFlightSearchRouteID", type="integer", nullable=false)
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="IDENTITY")
     */
    private $id;

    /**
     * @var RAFlightSearchQuery
     * @ORM\ManyToOne(targetEntity="AwardWallet\MainBundle\Entity\RAFlightSearchQuery")
     * @ORM\JoinColumns({
     *     @ORM\JoinColumn(name="RAFlightSearchQueryID", referencedColumnName="RAFlightSearchQueryID")
     * })
     */
    private $query;

    /**
     * @var RAFlightSearchRouteSegment[]
     * @ORM\OneToMany(targetEntity="AwardWallet\MainBundle\Entity\RAFlightSearchRouteSegment", mappedBy="route", cascade={"persist", "remove"})
     * @ORM\OrderBy({"id" = "ASC"})
     */
    private $segments;

    /**
     * @var string
     * @ORM\Column(name="DepCode", type="string", length=3, nullable=false)
     */
    private $depCode;

    /**
     * @var string
     * @ORM\Column(name="ArrCode", type="string", length=3, nullable=false)
     */
    private $arrCode;

    /**
     * @var string
     * @ORM\Column(name="ItineraryCOS", type="string", length=15, nullable=true)
     */
    private $itineraryCOS;

    /**
     * @var string
     * @ORM\Column(name="FlightDuration", type="string", length=100, nullable=true)
     */
    private $flightDuration;

    /**
     * @var int
     * @ORM\Column(name="FlightDurationSeconds", type="integer", nullable=true)
     */
    private $flightDurationSeconds;

    /**
     * @var string
     * @ORM\Column(name="LayoverDuration", type="string", length=100, nullable=true)
     */
    private $layoverDuration;

    /**
     * @var int
     * @ORM\Column(name="LayoverDurationSeconds", type="integer", nullable=true)
     */
    private $layoverDurationSeconds;

    /**
     * @var int
     * @ORM\Column(name="Stops", type="integer", nullable=true)
     */
    private $stops;

    /**
     * @var int
     * @ORM\Column(name="Tickets", type="integer", nullable=true)
     */
    private $tickets;

    /**
     * @var string
     * @ORM\Column(name="AwardTypes", type="string", length=250, nullable=true)
     */
    private $awardTypes;

    /**
     * @var string
     * @ORM\Column(name="MileCostProgram", type="string", length=250, nullable=true)
     */
    private $mileCostProgram;

    /**
     * @var int
     * @ORM\Column(name="MileCost", type="integer", nullable=true)
     */
    private $mileCost;

    /**
     * @var string
     * @ORM\Column(name="Currency", type="string", length=3, nullable=true)
     */
    private $currency;

    /**
     * @var float
     * @ORM\Column(name="ConversionRate", type="decimal", precision=10, scale=2, nullable=true)
     */
    private $conversionRate;

    /**
     * @var float
     * @ORM\Column(name="Taxes", type="decimal", precision=10, scale=2, nullable=true)
     */
    private $taxes;

    /**
     * @var float
     * @ORM\Column(name="Fees", type="decimal", precision=10, scale=2, nullable=true)
     */
    private $fees;

    /**
     * @var int
     * @ORM\Column(name="TotalDistance", type="integer", nullable=false)
     */
    private $totalDistance;

    /**
     * @var string
     * @ORM\Column(name="Parser", type="string", length=250, nullable=false)
     */
    private $parser;

    /**
     * @var string
     * @ORM\Column(name="ApiRequestID", type="string", length=100, nullable=false)
     */
    private $apiRequestID;

    /**
     * @var int
     * @ORM\Column(name="TimesFound", type="integer", nullable=false)
     */
    private $timesFound = 1;

    /**
     * @var \DateTime
     * @ORM\Column(name="CreateDate", type="datetime", nullable=false)
     */
    private $createDate;

    /**
     * @var \DateTime
     * @ORM\Column(name="LastSeenDate", type="datetime", nullable=false)
     */
    private $lastSeenDate;

    /**
     * @var bool
     * @ORM\Column(name="Archived", type="boolean", nullable=false)
     */
    private $archived = false;

    /**
     * @var bool
     * @ORM\Column(name="Flag", type="boolean", nullable=false)
     */
    private $flag = false;

    public function __construct()
    {
        $this->segments = new ArrayCollection();
        $this->createDate = new \DateTime();
        $this->lastSeenDate = new \DateTime();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getQuery(): ?RAFlightSearchQuery
    {
        return $this->query;
    }

    public function setQuery(RAFlightSearchQuery $query): self
    {
        $this->query = $query;

        return $this;
    }

    /**
     * @return RAFlightSearchRouteSegment[]|ArrayCollection
     */
    public function getSegments()
    {
        return $this->segments;
    }

    public function addSegment(RAFlightSearchRouteSegment $segment): self
    {
        $segment->setRoute($this);
        $this->segments->add($segment);
        $this->updateDepArrCodes();

        return $this;
    }

    public function removeSegment(RAFlightSearchRouteSegment $segment): self
    {
        $this->segments->removeElement($segment);
        $this->updateDepArrCodes();

        return $this;
    }

    public function getDepCode(): ?string
    {
        return $this->depCode;
    }

    public function setDepCode(string $depCode): self
    {
        $this->depCode = $depCode;

        return $this;
    }

    public function getArrCode(): ?string
    {
        return $this->arrCode;
    }

    public function setArrCode(string $arrCode): self
    {
        $this->arrCode = $arrCode;

        return $this;
    }

    public function getItineraryCOS(): ?string
    {
        return $this->itineraryCOS;
    }

    public function setItineraryCOS(?string $itineraryCOS): self
    {
        $this->itineraryCOS = $itineraryCOS;

        return $this;
    }

    public function updateDepArrCodes(): void
    {
        $segments = $this->getSegments();
        $firstSegment = $segments->first();
        $lastSegment = $segments->last();
        $this->setDepCode($firstSegment->getDepCode());
        $this->setArrCode($lastSegment->getArrCode());
    }

    public function getFlightDuration(): ?string
    {
        return $this->flightDuration;
    }

    public function setFlightDuration(?string $flightDuration): self
    {
        $this->flightDuration = $flightDuration;
        $this->setFlightDurationSeconds(
            !is_null($flightDuration) ? Duration::parseSeconds($flightDuration) : null
        );

        return $this;
    }

    public function getFlightDurationSeconds(): ?int
    {
        return $this->flightDurationSeconds;
    }

    public function setFlightDurationSeconds(?int $flightDurationSeconds): self
    {
        $this->flightDurationSeconds = $flightDurationSeconds;

        return $this;
    }

    public function getLayoverDuration(): ?string
    {
        return $this->layoverDuration;
    }

    public function setLayoverDuration(?string $layoverDuration): self
    {
        $this->layoverDuration = $layoverDuration;
        $this->setLayoverDurationSeconds(
            !is_null($layoverDuration) ? Duration::parseSeconds($layoverDuration) : null
        );

        return $this;
    }

    public function getLayoverDurationSeconds(): ?int
    {
        return $this->layoverDurationSeconds;
    }

    public function setLayoverDurationSeconds(?int $layoverDurationSeconds): self
    {
        $this->layoverDurationSeconds = $layoverDurationSeconds;

        return $this;
    }

    public function getTotalDuration(): int
    {
        $flightDuration = $this->getFlightDurationSeconds();
        $layoverDuration = $this->getLayoverDurationSeconds();
        $totalDuration = 0;

        if ($flightDuration) {
            $totalDuration += $flightDuration;
        }

        if ($layoverDuration) {
            $totalDuration += $layoverDuration;
        }

        return $totalDuration;
    }

    public function getStops(): ?int
    {
        return $this->stops;
    }

    public function setStops(?int $stops): self
    {
        $this->stops = $stops;

        return $this;
    }

    public function getTickets(): ?int
    {
        return $this->tickets;
    }

    public function setTickets(?int $tickets): self
    {
        $this->tickets = $tickets;

        return $this;
    }

    public function getAwardTypes(): ?string
    {
        return $this->awardTypes;
    }

    public function setAwardTypes(?string $awardTypes): self
    {
        $this->awardTypes = $awardTypes;

        return $this;
    }

    public function getMileCostProgram(): ?string
    {
        return $this->mileCostProgram;
    }

    public function setMileCostProgram(?string $mileCostProgram): self
    {
        $this->mileCostProgram = $mileCostProgram;

        return $this;
    }

    public function getMileCost(): ?int
    {
        return $this->mileCost;
    }

    public function setMileCost(?int $mileCost): self
    {
        $this->mileCost = $mileCost;

        return $this;
    }

    public function getCurrency(): ?string
    {
        return $this->currency;
    }

    public function setCurrency(?string $currency): self
    {
        $this->currency = $currency;

        return $this;
    }

    public function getConversionRate(): ?float
    {
        return $this->conversionRate;
    }

    public function setConversionRate(?float $conversionRate): self
    {
        $this->conversionRate = $conversionRate;

        return $this;
    }

    public function getTaxes(): ?float
    {
        return $this->taxes;
    }

    public function setTaxes(?float $taxes): self
    {
        $this->taxes = $taxes;

        return $this;
    }

    public function getFees(): ?float
    {
        return $this->fees;
    }

    public function setFees(?float $fees): self
    {
        $this->fees = $fees;

        return $this;
    }

    public function getTotalTaxesAndFees(): float
    {
        return ($this->getTaxes() ?? 0) + ($this->getFees() ?? 0);
    }

    public function getTotalDistance(): ?int
    {
        return $this->totalDistance;
    }

    public function setTotalDistance(int $totalDistance): self
    {
        $this->totalDistance = $totalDistance;

        return $this;
    }

    public function getParser(): ?string
    {
        return $this->parser;
    }

    public function setParser(string $parser): self
    {
        $this->parser = $parser;

        return $this;
    }

    public function getApiRequestID(): ?string
    {
        return $this->apiRequestID;
    }

    public function setApiRequestID(string $apiRequestID): self
    {
        $this->apiRequestID = $apiRequestID;

        return $this;
    }

    public function getTimesFound(): int
    {
        return $this->timesFound;
    }

    public function setTimesFound(int $timesFound): self
    {
        $this->timesFound = $timesFound;

        return $this;
    }

    public function incrementTimesFound(): self
    {
        $this->timesFound++;

        return $this;
    }

    public function getCreateDate(): \DateTime
    {
        return $this->createDate;
    }

    public function setCreateDate(\DateTime $createDate): self
    {
        $this->createDate = $createDate;

        return $this;
    }

    public function getLastSeenDate(): \DateTime
    {
        return $this->lastSeenDate;
    }

    public function setLastSeenDate(\DateTime $lastSeenDate): self
    {
        $this->lastSeenDate = $lastSeenDate;

        return $this;
    }

    public function isArchived(): bool
    {
        return $this->archived;
    }

    public function setArchived(bool $archived): self
    {
        $this->archived = $archived;

        return $this;
    }

    public function isFlag(): bool
    {
        return $this->flag;
    }

    public function setFlag(bool $flag): self
    {
        $this->flag = $flag;

        return $this;
    }

    public function equals(RAFlightSearchRoute $route): bool
    {
        if ($this->getDepCode() !== $route->getDepCode()) {
            return false;
        }

        if ($this->getArrCode() !== $route->getArrCode()) {
            return false;
        }

        if ($this->getFlightDurationSeconds() !== $route->getFlightDurationSeconds()) {
            return false;
        }

        if ($this->getLayoverDurationSeconds() !== $route->getLayoverDurationSeconds()) {
            return false;
        }

        if ($this->getMileCost() !== $route->getMileCost()) {
            return false;
        }

        if ($this->getTotalDistance() !== $route->getTotalDistance()) {
            return false;
        }

        if ($this->getParser() !== $route->getParser()) {
            return false;
        }

        /** @var RAFlightSearchRouteSegment[] $segments */
        $segments = $this->getSegments()->toArray();
        $routeSegments = $route->getSegments()->toArray();

        if (\count($segments) !== \count($routeSegments)) {
            return false;
        }

        $matchedSegments = [];

        foreach ($segments as $segment) {
            foreach ($routeSegments as $routeSegment) {
                if (in_array($routeSegment, $matchedSegments, true)) {
                    continue;
                }

                if ($segment->equals($routeSegment)) {
                    $matchedSegments[] = $routeSegment;

                    break;
                }
            }
        }

        if (count($matchedSegments) !== count($segments)) {
            return false;
        }

        if (abs(($this->getTaxes() ?? 0) - ($route->getTaxes() ?? 0)) > 20) {
            return false;
        }

        if (abs(($this->getFees() ?? 0) - ($route->getFees() ?? 0)) > 20) {
            return false;
        }

        return true;
    }

    public function updateByRoute(RAFlightSearchRoute $route): self
    {
        /** @var RAFlightSearchRouteSegment[] $segments */
        $segments = $this->getSegments()->toArray();
        /** @var RAFlightSearchRouteSegment[] $routeSegments */
        $routeSegments = $route->getSegments()->toArray();
        $updatedSegments = [];

        foreach ($segments as $segment) {
            foreach ($routeSegments as $routeSegment) {
                if (in_array($routeSegment, $updatedSegments, true)) {
                    continue;
                }

                if ($segment->equals($routeSegment)) {
                    $segment->updateBySegment($routeSegment);
                    $updatedSegments[] = $routeSegment;

                    break;
                }
            }
        }

        $this->updateDepArrCodes();
        $this->setFlightDuration($route->getFlightDuration());
        $this->setLayoverDuration($route->getLayoverDuration());
        $this->setStops($route->getStops());
        $this->setTickets($route->getTickets());
        $this->setItineraryCOS($route->getItineraryCOS());
        $this->setAwardTypes($route->getAwardTypes());
        $this->setMileCostProgram($route->getMileCostProgram());
        $this->setMileCost($route->getMileCost());
        $this->setCurrency($route->getCurrency());
        $this->setConversionRate($route->getConversionRate());
        $this->setTaxes(\max($this->getTaxes() ?? 0, $route->getTaxes() ?? 0));
        $this->setFees(\max($this->getFees() ?? 0, $route->getFees() ?? 0));
        $this->setTotalDistance($route->getTotalDistance());
        $this->setParser($route->getParser());
        $this->setApiRequestID($route->getApiRequestID());
        $this->setTimesFound($this->getTimesFound() + $route->getTimesFound());
        $this->setLastSeenDate(\max($this->getLastSeenDate(), $route->getLastSeenDate()));
        $this->setArchived($this->isArchived() || $route->isArchived());
        $this->setFlag($this->isFlag() || $route->isFlag());

        return $this;
    }
}
