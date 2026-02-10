<?php

namespace AwardWallet\MainBundle\Service\TravelSummary\Data;

use AwardWallet\MainBundle\DependencyInjection\Annotation\NoDI;

/**
 * Class referring to reservations that have two points on the map and have a route.
 *
 * @NoDI
 */
class Trip implements ItineraryModelInterface
{
    /**
     * Trip ID.
     */
    private int $id;
    /**
     * Trip segment ID.
     */
    private int $segmentId;
    /**
     * Title for a marker on the map.
     */
    private string $title;
    /**
     * Two-character airline code (used for flights).
     */
    private ?string $airlineCode = null;
    /**
     * Departure date.
     */
    private \DateTime $startDate;
    /**
     * Arrival date.
     */
    private \DateTime $endDate;
    /**
     * An object containing information about the point of departure.
     */
    private Marker $departure;
    /**
     * An object containing information about the point of arrival.
     */
    private Marker $arrival;
    /**
     * Prefix used for timeline links.
     */
    private string $prefix;
    /**
     * Duration in days.
     */
    private ?string $duration = null;
    /**
     * A confirmation number.
     */
    private ?string $confirmationNumber = null;

    public function __construct(
        int $segmentId,
        string $title,
        \DateTime $startDate,
        \DateTime $endDate,
        Marker $departure,
        Marker $arrival,
        string $prefix
    ) {
        $this->segmentId = $segmentId;
        $this->title = $title;
        $this->startDate = $startDate;
        $this->endDate = $endDate;
        $this->departure = $departure;
        $this->arrival = $arrival;
        $this->prefix = $prefix;
    }

    public function getId(): int
    {
        return $this->id;
    }

    public function setId(int $id): self
    {
        $this->id = $id;

        return $this;
    }

    public function getSegmentId(): int
    {
        return $this->segmentId;
    }

    public function getTitle(): string
    {
        return $this->title;
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

    public function getStartDate(): \DateTime
    {
        return $this->startDate;
    }

    public function getEndDate(): \DateTime
    {
        return $this->endDate;
    }

    public function getDeparture(): Marker
    {
        return $this->departure;
    }

    public function getArrival(): Marker
    {
        return $this->arrival;
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

    public function getStartLocation(): string
    {
        return ItineraryModelHelper::getLocation($this->departure);
    }

    public function getEndLocation(): string
    {
        return ItineraryModelHelper::getLocation($this->arrival);
    }
}
