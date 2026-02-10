<?php

namespace AwardWallet\MainBundle\Service\TravelSummary\Data;

use AwardWallet\MainBundle\DependencyInjection\Annotation\NoDI;
use AwardWallet\MainBundle\Entity\Plan;
use AwardWallet\MainBundle\Entity\Rental as RentalEntity;
use AwardWallet\MainBundle\Entity\Trip as TripEntity;

/**
 * @NoDI()
 */
class TripSegment
{
    public const TYPE_DEPARTURE = 0;
    public const TYPE_ARRIVAL = 1;

    /**
     * Trip segment ID.
     */
    private int $id;
    /**
     * Departure date.
     */
    private \DateTime $startDate;
    /**
     * Arrival date.
     */
    private ?\DateTime $endDate;
    /**
     * Duration in days or hours.
     */
    private ?string $duration;
    /**
     * Segment description for when reservation names are different, but location is the same.
     */
    private ?string $details = null;
    /**
     * Prefix used for timeline links.
     */
    private string $prefix;
    /**
     * Title of the travel plan if this reservation is included.
     */
    private ?string $travelPlan = null;
    /**
     * Flag indicating whether the reservation has a confirmation number.
     */
    private bool $hasConfirmationNumber;
    /**
     * The type of segment used for trips (buses, trains, ferries).
     */
    private ?int $type = null;

    public function __construct(
        ItineraryModelInterface $model,
        string $prefix,
        array $plans
    ) {
        $this->id = $model->getSegmentId();
        $this->startDate = $model->getStartDate();
        $this->endDate = $model->getEndDate();
        $this->duration = $model->getDuration();
        $this->prefix = $prefix;
        $this->setPlanToSegment($model, $plans);
        $this->hasConfirmationNumber = $model->getConfirmationNumber() !== null;
    }

    public function getId(): int
    {
        return $this->id;
    }

    public function getStartDate(): \DateTime
    {
        return $this->startDate;
    }

    public function getEndDate(): ?\DateTime
    {
        return $this->endDate;
    }

    public function getDate(): \DateTime
    {
        switch ($this->prefix) {
            case RentalEntity::SEGMENT_MAP_START:
                return $this->startDate;

            case RentalEntity::SEGMENT_MAP_END:
                return $this->endDate;

            case TripEntity::SEGMENT_MAP:
                return ($this->type === self::TYPE_DEPARTURE) ? $this->startDate : $this->endDate;

            default:
                return $this->startDate;
        }
    }

    public function getDuration(): ?string
    {
        return $this->duration;
    }

    public function getDetails(): ?string
    {
        return $this->details;
    }

    public function setDetails(?string $details): self
    {
        $this->details = $details;

        return $this;
    }

    public function getPrefix(): string
    {
        return $this->prefix;
    }

    public function getTravelPlan(): ?string
    {
        return $this->travelPlan;
    }

    public function isHasConfirmationNumber(): bool
    {
        return $this->hasConfirmationNumber;
    }

    public function getType(): ?int
    {
        return $this->type;
    }

    public function setType(?int $type): self
    {
        $this->type = $type;

        return $this;
    }

    /**
     * Set the name of the travel plan for the trip segment.
     *
     * @param ItineraryModelInterface $model a reservation class object
     * @param Plan[] $plans an array with travel plans
     */
    private function setPlanToSegment(ItineraryModelInterface $model, array $plans)
    {
        $startTimestamp = $model->getStartDate()->getTimestamp();
        $travelPlan = '';

        foreach ($plans as $plan) {
            if (
                $startTimestamp >= $plan->getStartDate()->getTimestamp()
                && $startTimestamp <= $plan->getEndDate()->getTimestamp()
            ) {
                $travelPlan = $plan->getName();

                break;
            }
        }

        $this->travelPlan = $travelPlan;
    }
}
