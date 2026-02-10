<?php

namespace AwardWallet\MainBundle\Service\TravelSummary\Data;

interface ItineraryModelInterface
{
    /**
     * Get the segment ID of a trip or reservation.
     */
    public function getSegmentId(): int;

    /**
     * Get the title for a marker (name of airline, hotel, parking company, restaurant).
     */
    public function getTitle(): ?string;

    /**
     * Get the departure date.
     */
    public function getStartDate(): \DateTime;

    /**
     * Get the arrival date.
     */
    public function getEndDate(): ?\DateTime;

    /**
     * Get the prefix for timeline links.
     */
    public function getPrefix(): string;

    /**
     * Get the duration of the trip or reservation.
     */
    public function getDuration(): ?string;

    /**
     * Get the confirmation number, which is used to filter reservations in case there are duplicate segments.
     * If the reservations have the same dates, geographical coordinates, categories, but one of them
     * does not have a code, this reservation will not be displayed.
     */
    public function getConfirmationNumber(): ?string;
}
