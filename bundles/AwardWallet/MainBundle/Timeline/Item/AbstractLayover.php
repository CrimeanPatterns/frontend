<?php

namespace AwardWallet\MainBundle\Timeline\Item;

use AwardWallet\MainBundle\Entity\Tripsegment;

abstract class AbstractLayover extends AbstractItinerary implements LayoverInterface
{
    protected Tripsegment $leftSource;

    protected Tripsegment $rightSource;

    protected string $location;

    protected \DateInterval $duration;

    public function __construct(
        AbstractItinerary $left,
        AbstractItinerary $right,
        string $location
    ) {
        $leftSource = $left->getSource();
        $rightSource = $right->getSource();

        parent::__construct(
            $leftSource->getId(),
            $leftSource->getUTCEndDate(),
            $right->getStartDate(),
            $leftSource->getArrivalDate()
        );

        $this->leftSource = $leftSource;
        $this->rightSource = $rightSource;
        $this->location = $location;
        $this->duration = $rightSource->getDepartureDate()->diff($leftSource->getArrivalDate());
    }

    public function getLeftSource(): Tripsegment
    {
        return $this->leftSource;
    }

    public function getRightSource(): Tripsegment
    {
        return $this->rightSource;
    }

    public function getLocation(): string
    {
        return $this->location;
    }

    public function getDuration(): \DateInterval
    {
        return $this->duration;
    }

    public function getPrefix(): string
    {
        return 'L';
    }
}
