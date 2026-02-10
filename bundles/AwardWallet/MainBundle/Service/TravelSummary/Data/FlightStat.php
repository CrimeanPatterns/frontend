<?php

namespace AwardWallet\MainBundle\Service\TravelSummary\Data;

use AwardWallet\MainBundle\DependencyInjection\Annotation\NoDI;

/**
 * @NoDI
 */
class FlightStat implements \JsonSerializable
{
    /**
     * @var int
     */
    private $totalFlights;
    /**
     * @var int
     */
    private $longHaulFlights;
    /**
     * @var int
     */
    private $shortHaulFlights;

    public function __construct(
        int $totalFlights,
        int $longHaulFlights,
        int $shortHaulFlights
    ) {
        $this->totalFlights = $totalFlights;
        $this->longHaulFlights = $longHaulFlights;
        $this->shortHaulFlights = $shortHaulFlights;
    }

    public function getTotalFlights(): int
    {
        return $this->totalFlights;
    }

    public function getLongHaulFlights(): int
    {
        return $this->longHaulFlights;
    }

    public function getShortHaulFlights(): int
    {
        return $this->shortHaulFlights;
    }

    /**
     * Get the percentage of long-haul flights.
     */
    public function getLongHaulPercentage(): int
    {
        return $this->longHaulFlights ? round($this->longHaulFlights / $this->totalFlights * 100) : 0;
    }

    /**
     * Get the percentage of short-haul flights.
     */
    public function getShortHaulPercentage(): int
    {
        return $this->shortHaulFlights ? round($this->shortHaulFlights / $this->totalFlights * 100) : 0;
    }

    public function jsonSerialize()
    {
        return [
            'totalFlights' => $this->totalFlights,
            'longHaulFlights' => $this->longHaulFlights,
            'shortHaulFlights' => $this->shortHaulFlights,
            'longHaulPercentage' => $this->getLongHaulPercentage(),
            'shortHaulPercentage' => $this->getShortHaulPercentage(),
        ];
    }
}
