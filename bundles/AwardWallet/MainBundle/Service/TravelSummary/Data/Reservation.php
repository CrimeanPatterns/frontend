<?php

namespace AwardWallet\MainBundle\Service\TravelSummary\Data;

use AwardWallet\MainBundle\DependencyInjection\Annotation\NoDI;

/**
 * Class referring to reservations that have only one point on the map and have no route.
 *
 * @NoDI()
 */
class Reservation implements ItineraryModelInterface
{
    /**
     * Reservation ID.
     */
    private int $segmentId;
    /**
     * Title for a marker on the map.
     */
    private ?string $title;
    /**
     * Departure date.
     */
    private \DateTime $startDate;
    /**
     * Arrival date.
     */
    private ?\DateTime $endDate;
    /**
     * An object containing latitude, longitude and time zone.
     */
    private Marker $marker;
    /**
     * Prefix used for timeline links.
     */
    private string $prefix;
    /**
     * Duration in days or hours.
     */
    private ?string $duration = null;
    /**
     * A confirmation number.
     */
    private ?string $confirmationNumber = null;

    public function __construct(
        int $id,
        ?string $title,
        \DateTime $startDate,
        ?\DateTime $endDate,
        Marker $marker,
        string $prefix
    ) {
        $this->segmentId = $id;
        $this->title = $title;
        $this->startDate = $startDate;
        $this->endDate = $endDate;
        $this->marker = $marker;
        $this->prefix = $prefix;
    }

    public function getSegmentId(): int
    {
        return $this->segmentId;
    }

    public function getTitle(): ?string
    {
        return $this->title;
    }

    public function getStartDate(): \DateTime
    {
        return $this->startDate;
    }

    public function getEndDate(): ?\DateTime
    {
        return $this->endDate;
    }

    public function getMarker(): Marker
    {
        return $this->marker;
    }

    public function getPrefix(): string
    {
        return $this->prefix;
    }

    public function getDuration(): ?string
    {
        return $this->duration;
    }

    public function setDuration(?string $duration): self
    {
        $this->duration = $duration;

        return $this;
    }

    public function getConfirmationNumber(): ?string
    {
        return $this->confirmationNumber;
    }

    public function setConfirmationNumber(?string $number): self
    {
        $this->confirmationNumber = $number;

        return $this;
    }

    public function getCurrentLocation(): string
    {
        return ItineraryModelHelper::getLocation($this->marker);
    }
}
