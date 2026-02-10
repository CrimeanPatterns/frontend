<?php

namespace AwardWallet\MainBundle\Service\RA\Flight\DTO;

class ParserSelectorRequest
{
    /**
     * @var string[][]
     */
    private array $routes = [];

    /**
     * @var \DateTime[]
     */
    private array $dates = [];

    /**
     * @var string[]
     */
    private array $flightClasses = [];

    /**
     * @var int[]
     */
    private array $passengersCount = [];

    public function addRoute(string $from, string $to): self
    {
        $this->routes[] = [strtoupper($from), strtoupper($to)];

        return $this;
    }

    public function addRoutes(array $from, array $to): self
    {
        foreach ($from as $fromAirport) {
            foreach ($to as $toAirport) {
                $this->addRoute($fromAirport, $toAirport);
            }
        }

        return $this;
    }

    public function getRoutes(): array
    {
        return $this->routes;
    }

    public function addDate(\DateTime $date): self
    {
        $this->dates[] = $date;

        return $this;
    }

    /**
     * @param \DateTime[] $dates
     */
    public function addDates(array $dates): self
    {
        foreach ($dates as $date) {
            $this->addDate($date);
        }

        return $this;
    }

    public function getDates(): array
    {
        return $this->dates;
    }

    public function addFlightClass(string $flightClass): self
    {
        $this->flightClasses[] = $flightClass;

        return $this;
    }

    public function addFlightClasses(array $flightClasses): self
    {
        foreach ($flightClasses as $flightClass) {
            $this->addFlightClass($flightClass);
        }

        return $this;
    }

    public function getFlightClasses(): array
    {
        return $this->flightClasses;
    }

    public function addPassengersCount(int $passengersCount): self
    {
        $this->passengersCount[] = $passengersCount;

        return $this;
    }

    public function getPassengersCount(): array
    {
        return $this->passengersCount;
    }
}
