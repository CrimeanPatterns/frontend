<?php

namespace AwardWallet\MainBundle\Service\Itinerary\Form\Model;

use AwardWallet\MainBundle\Entity\Tripsegment;
use AwardWallet\MainBundle\Service\Itinerary\Form\Validator\DateSequence;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * @DateSequence()
 */
abstract class AbstractSegmentModel implements DateSequenceInterface
{
    /**
     * @Assert\NotBlank()
     */
    protected ?\DateTime $departureDate = null;

    /**
     * @Assert\NotBlank()
     */
    protected ?\DateTime $arrivalDate = null;

    protected ?Tripsegment $tripSegment = null;

    public function getDepartureDate(): ?\DateTime
    {
        return $this->departureDate;
    }

    public function setDepartureDate(?\DateTime $departureDate): self
    {
        $this->departureDate = $departureDate;

        return $this;
    }

    public function getArrivalDate(): ?\DateTime
    {
        return $this->arrivalDate;
    }

    public function setArrivalDate(?\DateTime $arrivalDate): self
    {
        $this->arrivalDate = $arrivalDate;

        return $this;
    }

    public function getTripSegment(): ?Tripsegment
    {
        return $this->tripSegment;
    }

    public function setTripSegment(?Tripsegment $tripSegment): self
    {
        $this->tripSegment = $tripSegment;

        return $this;
    }

    public function getStartDate(): ?\DateTime
    {
        return $this->getDepartureDate();
    }

    public function getEndDate(): ?\DateTime
    {
        return $this->getArrivalDate();
    }

    public function getDateSequenceViolationMessage(): string
    {
        return 'itineraries.dates-inconsistent';
    }

    public function getDateSequenceViolationPath(): string
    {
        return 'departureDate';
    }
}
