<?php

namespace AwardWallet\MainBundle\Timeline\Item;

use AwardWallet\MainBundle\Entity\Provider;
use AwardWallet\MainBundle\Entity\Trip;
use AwardWallet\MainBundle\Entity\Tripsegment;
use AwardWallet\MainBundle\Service\MileValue\MileValueAlternativeFlightsItem;
use AwardWallet\MainBundle\Timeline\Diff\TripSource;
use AwardWallet\MainBundle\Timeline\TripInfo\TripInfo;

/**
 * @property Tripsegment $source
 */
abstract class AbstractTrip extends AbstractItinerary implements LayoverBoundaryInterface, CanCreatePlanInterface
{
    use LayoverBoundaryTrait;
    use CanCreatePlanTrait;

    protected TripInfo $tripInfo;

    protected ?MileValueAlternativeFlightsItem $mileValue = null;
    protected bool $isOverseasTrip = false;

    public function __construct(Tripsegment $tripsegment, ?Provider $provider = null)
    {
        $trip = $tripsegment->getTripid();
        parent::__construct(
            $tripsegment->getId(),
            $tripsegment->getUTCStartDate(),
            $tripsegment->getUTCEndDate(),
            $tripsegment->getDepartureDate(),
            $tripsegment,
            $trip->getConfirmationNumber(true),
            $trip->getAccount(),
            $provider ?? $trip->getProvider(),
            $tripsegment->getDepgeotagid(),
            null,
            !empty($tripsegment->getChangedate())
        );

        $this->tripInfo = TripInfo::createFromTripSegment($tripsegment);
        $this->setConfNo(
            $this->tripInfo->primaryConfirmationNumberInfo->confirmationNumber
            ?? $this->tripInfo->secondaryConfirmationNumber
            ?? $tripsegment->getTripid()->getConfirmationNumber(true)
        );
    }

    public function getTripInfo(): TripInfo
    {
        return $this->tripInfo;
    }

    public function getMileValue(): ?MileValueAlternativeFlightsItem
    {
        return $this->mileValue;
    }

    public function setMileValue(?MileValueAlternativeFlightsItem $mileValue): void
    {
        $this->mileValue = $mileValue;
    }

    public function getDiffSourceId()
    {
        return TripSource::getSourceId($this->source);
    }

    public function getPrefix(): string
    {
        return Trip::getSegmentMap()[0];
    }

    public function getType(): string
    {
        return Type::TRIP;
    }

    public function setIsOverseasTrip(bool $isOverseas): self
    {
        $this->isOverseasTrip = $isOverseas;

        return $this;
    }

    public function isOverseasTrip(): bool
    {
        return $this->isOverseasTrip;
    }
}
