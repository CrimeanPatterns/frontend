<?php

namespace AwardWallet\MainBundle\Loyalty\Resources\RewardAvailability;

use Doctrine\Common\Collections\ArrayCollection;
use JMS\Serializer\Annotation\Accessor;
use JMS\Serializer\Annotation\Type;

class Route
{
    /**
     * @Type("integer")
     * @var int
     */
    private $numberOfStops;
    /**
     * @Type("integer")
     * @var int
     */
    private $tickets;
    /**
     * @Type("string")
     * @var string
     */
    private $parsedDistance;
    /**
     * @Type("string")
     * @var string
     */
    private $awardTypes;
    /**
     * @Type("string")
     * @var string
     */
    private $cabinType;
    /**
     * @Type("string")
     * @var string
     */
    private $classOfService;
    /**
     * @Type("bool")
     * @var bool
     */
    private $isFastest;
    /**
     * @Type("bool")
     * @var bool
     */
    private $isCheapest;
    /**
     * @Type("AwardWallet\MainBundle\Loyalty\Resources\RewardAvailability\Times")
     * @var Times
     */
    private $times;
    /**
     * @Type("AwardWallet\MainBundle\Loyalty\Resources\RewardAvailability\Redemptions")
     * @var Redemptions
     */
    private $mileCost;
    /**
     * @Type("AwardWallet\MainBundle\Loyalty\Resources\RewardAvailability\Payments")
     * @var Payments
     */
    private $cashCost;
    /**
     * @Type("array<AwardWallet\MainBundle\Loyalty\Resources\RewardAvailability\Segment>")
     * @Accessor(getter="getSegmentsToSerialize", setter="setSegmentsFromSerialized")
     * @var Segment[]
     */
    private $segments;
    /**
     * @Type("string")
     * @var string
     */
    private $message;

    public function __construct(?int $numberOfStops, ?int $tickets, ?string $parsedDistance, ?string $awardTypes, Times $times, Redemptions $mileCost, Payments $cashCost, array $segments, ?string $message)
    {
        $this->numberOfStops = $numberOfStops;
        $this->tickets = $tickets;
        $this->parsedDistance = $parsedDistance;
        $this->awardTypes = $awardTypes;
        $this->times = $times;
        $this->mileCost = $mileCost;
        $this->cashCost = $cashCost;
        $this->segments = new ArrayCollection($segments);
        $this->message = $message;
    }

    public function getSegmentsToSerialize()
    {
        return $this->segments->getValues();
    }

    public function setSegmentsFromSerialized($segments)
    {
        $this->segments = new ArrayCollection($segments);
    }

    public function getMileCost(): Redemptions
    {
        return $this->mileCost;
    }

    public function getNumberOfStops(): ?int
    {
        return $this->numberOfStops;
    }

    public function getTickets(): ?int
    {
        return $this->tickets;
    }

    public function getParsedDistance(): ?string
    {
        return $this->parsedDistance;
    }

    public function getAwardTypes(): ?string
    {
        return $this->awardTypes;
    }

    public function getTimes(): Times
    {
        return $this->times;
    }

    public function getCashCost(): Payments
    {
        return $this->cashCost;
    }

    public function getCabinType(): ?string
    {
        return $this->cabinType;
    }

    public function getClassOfService(): ?string
    {
        return $this->classOfService;
    }

    public function isFastest(): bool
    {
        return $this->isFastest ?? false;
    }

    public function isCheapest(): bool
    {
        return $this->isCheapest ?? false;
    }
}
