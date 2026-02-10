<?php

namespace AwardWallet\MainBundle\Entity;

use AwardWallet\MainBundle\Service\RA\Flight\Duration;
use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Table(name="RAFlightSearchRouteSegment")
 * @ORM\Entity
 */
class RAFlightSearchRouteSegment
{
    /**
     * @var int
     * @ORM\Column(name="RAFlightSearchRouteSegmentID", type="integer", nullable=false)
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="IDENTITY")
     */
    private $id;

    /**
     * @var RAFlightSearchRoute
     * @ORM\ManyToOne(targetEntity="AwardWallet\MainBundle\Entity\RAFlightSearchRoute")
     * @ORM\JoinColumns({
     *   @ORM\JoinColumn(name="RAFlightSearchRouteID", referencedColumnName="RAFlightSearchRouteID")
     * })
     */
    private $route;

    /**
     * @var \DateTime
     * @ORM\Column(name="DepDate", type="datetime", nullable=false)
     */
    private $depDate;

    /**
     * @var string
     * @ORM\Column(name="DepCode", type="string", length=3, nullable=false)
     */
    private $depCode;

    /**
     * @var string
     * @ORM\Column(name="DepTerminal", type="string", length=50, nullable=true)
     */
    private $depTerminal;

    /**
     * @var \DateTime
     * @ORM\Column(name="ArrDate", type="datetime", nullable=false)
     */
    private $arrDate;

    /**
     * @var string
     * @ORM\Column(name="ArrCode", type="string", length=3, nullable=false)
     */
    private $arrCode;

    /**
     * @var string
     * @ORM\Column(name="ArrTerminal", type="string", length=50, nullable=true)
     */
    private $arrTerminal;

    /**
     * @var string
     * @ORM\Column(name="Meal", type="string", length=250, nullable=true)
     */
    private $meal;

    /**
     * @var string
     * @ORM\Column(name="Service", type="string", length=250, nullable=true)
     */
    private $service;

    /**
     * @var string
     * @ORM\Column(name="FareClass", type="string", length=250, nullable=true)
     */
    private $fareClass;

    /**
     * @var array
     * @ORM\Column(name="FlightNumbers", type="json", nullable=true)
     */
    private $flightNumbers;

    /**
     * @var string
     * @ORM\Column(name="AirlineCode", type="string", length=3, nullable=true)
     */
    private $airlineCode;

    /**
     * @var string
     * @ORM\Column(name="Aircraft", type="string", length=250, nullable=true)
     */
    private $aircraft;

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

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getRoute(): ?RAFlightSearchRoute
    {
        return $this->route;
    }

    public function setRoute(RAFlightSearchRoute $route): self
    {
        $this->route = $route;

        return $this;
    }

    public function getDepDate(): ?\DateTime
    {
        return $this->depDate;
    }

    public function setDepDate(\DateTime $depDate): self
    {
        $this->depDate = $depDate;

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

    public function getDepTerminal(): ?string
    {
        return $this->depTerminal;
    }

    public function setDepTerminal(?string $depTerminal): self
    {
        $this->depTerminal = $depTerminal;

        return $this;
    }

    public function getArrDate(): ?\DateTime
    {
        return $this->arrDate;
    }

    public function setArrDate(\DateTime $arrDate): self
    {
        $this->arrDate = $arrDate;

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

    public function getArrTerminal(): ?string
    {
        return $this->arrTerminal;
    }

    public function setArrTerminal(?string $arrTerminal): self
    {
        $this->arrTerminal = $arrTerminal;

        return $this;
    }

    public function getMeal(): ?string
    {
        return $this->meal;
    }

    public function setMeal(?string $meal): self
    {
        $this->meal = $meal;

        return $this;
    }

    public function getService(): ?string
    {
        return $this->service;
    }

    public function setService(?string $service): self
    {
        $this->service = $service;

        return $this;
    }

    public function getFareClass(): ?string
    {
        return $this->fareClass;
    }

    public function setFareClass(?string $fareClass): self
    {
        $this->fareClass = $fareClass;

        return $this;
    }

    public function getFlightNumbers(): ?array
    {
        return $this->flightNumbers;
    }

    public function setFlightNumbers(?array $flightNumbers): self
    {
        $this->flightNumbers = $flightNumbers;

        return $this;
    }

    public function getAirlineCode(): ?string
    {
        return $this->airlineCode;
    }

    public function setAirlineCode(?string $airlineCode): self
    {
        $this->airlineCode = $airlineCode;

        return $this;
    }

    public function getAircraft(): ?string
    {
        return $this->aircraft;
    }

    public function setAircraft(?string $aircraft): self
    {
        $this->aircraft = $aircraft;

        return $this;
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

    public function equals(RAFlightSearchRouteSegment $segment): bool
    {
        return $this->getDepCode() === $segment->getDepCode()
            && $this->getArrCode() === $segment->getArrCode()
            && $this->getDepDate()->format('Y-m-d H:i:s') === $segment->getDepDate()->format('Y-m-d H:i:s')
            && $this->getArrDate()->format('Y-m-d H:i:s') === $segment->getArrDate()->format('Y-m-d H:i:s')
            && (
                $this->getDepTerminal() === $segment->getDepTerminal()
                || is_null($this->getDepTerminal())
                || is_null($segment->getDepTerminal())
            )
            && (
                $this->getArrTerminal() === $segment->getArrTerminal()
                || is_null($this->getArrTerminal())
                || is_null($segment->getArrTerminal())
            )
            && (
                $this->getAirlineCode() === $segment->getAirlineCode()
                || is_null($this->getAirlineCode())
                || is_null($segment->getAirlineCode())
            );
    }

    public function updateBySegment(RAFlightSearchRouteSegment $segment): self
    {
        $this->setDepDate($segment->getDepDate());
        $this->setDepCode($segment->getDepCode());
        $this->setDepTerminal($segment->getDepTerminal());
        $this->setArrDate($segment->getArrDate());
        $this->setArrCode($segment->getArrCode());
        $this->setArrTerminal($segment->getArrTerminal());
        $this->setMeal($segment->getMeal());
        $this->setService($segment->getService());
        $this->setFareClass($segment->getFareClass());
        $this->setFlightNumbers($segment->getFlightNumbers());
        $this->setAirlineCode($segment->getAirlineCode());
        $this->setAircraft($segment->getAircraft());
        $this->setFlightDuration($segment->getFlightDuration());
        $this->setLayoverDuration($segment->getLayoverDuration());

        return $this;
    }
}
