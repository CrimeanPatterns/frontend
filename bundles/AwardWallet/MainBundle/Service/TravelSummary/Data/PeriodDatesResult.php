<?php

namespace AwardWallet\MainBundle\Service\TravelSummary\Data;

use AwardWallet\MainBundle\DependencyInjection\Annotation\NoDI;
use AwardWallet\MainBundle\Service\TravelSummary\PeriodDatesHelper;

/**
 * @NoDI()
 */
class PeriodDatesResult
{
    /**
     * @see PeriodDatesHelper::getMainPeriods()
     */
    private int $currentPeriod;
    /**
     * Start date for selecting reservations.
     */
    private \DateTime $startDate;
    /**
     * End date for selecting reservations.
     */
    private \DateTime $endDate;
    /**
     * Earliest year for which reservations are available.
     */
    private int $earliestYear;

    public function __construct(int $currentPeriod, \DateTime $startDate, \DateTime $endDate, int $earliestYear)
    {
        $this->currentPeriod = $currentPeriod;
        $this->startDate = $startDate;
        $this->endDate = $endDate;
        $this->earliestYear = $earliestYear;
    }

    public function getCurrentPeriod(): int
    {
        return $this->currentPeriod;
    }

    public function getStartDate(): \DateTime
    {
        return $this->startDate;
    }

    public function getEndDate(): \DateTime
    {
        return $this->endDate;
    }

    public function getEarliestYear(): int
    {
        return $this->earliestYear;
    }

    /**
     * Get the earliest year or selected year based on period.
     */
    public function getYear(): ?int
    {
        if ($this->currentPeriod === PeriodDatesHelper::ALL_TIME) {
            return $this->earliestYear;
        } elseif (strlen((string) $this->currentPeriod) === 4) {
            return $this->currentPeriod;
        }

        return null;
    }
}
