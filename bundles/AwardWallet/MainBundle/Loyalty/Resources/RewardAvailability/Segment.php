<?php

namespace AwardWallet\MainBundle\Loyalty\Resources\RewardAvailability;

use JMS\Serializer\Annotation\Type;

class Segment
{
    /**
     * @Type("AwardWallet\MainBundle\Loyalty\Resources\RewardAvailability\SegmentPoint")
     * @var SegmentPoint
     */
    private $departure;
    /**
     * @Type("AwardWallet\MainBundle\Loyalty\Resources\RewardAvailability\SegmentPoint")
     * @var SegmentPoint
     */
    private $arrival;
    /**
     * @Type("string")
     * @var string
     */
    private $meal;
    /**
     * @Type("string")
     * @var string
     */
    private $cabin;
    /**
     * @Type("string")
     * @var string
     */
    private $fareClass;
    /**
     * @Type("integer")
     * @var int
     */
    private $tickets;
    /**
     * @Type("array<string>")
     * @var array
     */
    private $flightNumbers;
    /**
     * @Type("string")
     * @var string
     */
    private $airlineCode;
    /**
     * @Type("string")
     * @var string
     */
    private $aircraft;
    /**
     * @Type("AwardWallet\MainBundle\Loyalty\Resources\RewardAvailability\Times")
     * @var Times
     */
    private $times;
    /**
     * @Type("integer")
     * @var int
     */
    private $numberOfStops;
    /**
     * @Type("string")
     * @var string
     */
    private $classOfService;

    public function __construct(SegmentPoint $departure, SegmentPoint $arrival, ?string $meal, ?string $cabin, ?string $fareClass, ?int $tickets, ?array $flightNumbers, ?string $airlineCode, ?string $aircraft, Times $times, ?int $numberOfStops, ?string $classOfService)
    {
        $this->departure = $departure;
        $this->arrival = $arrival;
        $this->meal = $meal;
        $this->cabin = $cabin;
        $this->fareClass = $fareClass;
        $this->tickets = $tickets;
        $this->flightNumbers = $flightNumbers;
        $this->airlineCode = $airlineCode;
        $this->aircraft = $aircraft;
        $this->times = $times;
        $this->classOfService = $classOfService;
    }

    public function getAircraft(): ?string
    {
        return $this->aircraft;
    }

    public function getAirlineCode(): ?string
    {
        return $this->airlineCode;
    }

    public function getDepartAirport(): ?string
    {
        return $this->departure->getAirport();
    }

    public function getArrivalAirport(): ?string
    {
        return $this->arrival->getAirport();
    }

    public function getDepartDate(): ?\DateTime
    {
        return $this->departure->getDateTime();
    }

    public function getArrivalDate(): ?\DateTime
    {
        return $this->arrival->getDateTime();
    }

    public function getCabin(): ?string
    {
        return $this->cabin;
    }

    public function getFareClass(): ?string
    {
        return $this->fareClass;
    }

    public function getTickets(): ?int
    {
        return $this->tickets;
    }

    public function getClassOfService(): ?string
    {
        return $this->classOfService;
    }
}
