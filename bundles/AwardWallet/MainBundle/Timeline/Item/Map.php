<?php

namespace AwardWallet\MainBundle\Timeline\Item;

class Map
{
    /**
     * 'JFK, BUF, etc'.
     */
    public array $points;
    /**
     * @var \DateTime|bool
     */
    public $arrDate;
    /**
     * The property records station codes for reservations of type: Bus, Train, Ferry, Transfer.
     */
    private array $stationCodes = [];

    /**
     * @param \DateTime|bool $arrDate
     */
    public function __construct(array $points, $arrDate)
    {
        $this->points = $points;
        $this->arrDate = $arrDate;
    }

    public function getStationCodes(): array
    {
        return $this->stationCodes;
    }

    public function setStationCodes(array $stationCodes): self
    {
        $this->stationCodes = $stationCodes;

        return $this;
    }
}
