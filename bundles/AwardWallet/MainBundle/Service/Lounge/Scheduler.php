<?php

namespace AwardWallet\MainBundle\Service\Lounge;

use Clock\ClockInterface;

class Scheduler
{
    private ClockInterface $clock;

    private \DateTime $startDate;

    private int $updateFrequency;

    private int $numberOfIterations;

    public function __construct(
        ClockInterface $clock,
        ?\DateTime $startDate = null,
        int $updateFrequency = 5,
        int $numberOfIterations = 4
    ) {
        $this->clock = $clock;

        if (is_null($startDate)) {
            $startDate = new \DateTime('2025-05-20');
        }

        $this->startDate = $startDate;
        $this->updateFrequency = $updateFrequency;
        $this->numberOfIterations = $numberOfIterations;
    }

    /**
     * @return int days
     */
    public function getUpdateFrequency(): int
    {
        return $this->updateFrequency;
    }

    /**
     * @return int 1 iteration = 1/X
     */
    public function getNumberOfIterations(): int
    {
        return $this->numberOfIterations;
    }

    /**
     * @return \DateTime start date first iteration
     */
    public function getStartDate(): \DateTime
    {
        return $this->startDate;
    }

    public function getCurrentUpdateIteration(?int $days = null): int
    {
        $days = max($days ?? $this->getCurrentDays(), 0);

        return floor($days / $this->getUpdateFrequency());
    }

    public function isItsTimeForScan(?int $days = null): bool
    {
        $days = $days ?? $this->getCurrentDays();

        if ($days < 0) {
            return false;
        }

        return ($days % $this->getUpdateFrequency()) === 0;
    }

    public function getSchedule(int $futureLimit = 3, int $pastLimit = 2): array
    {
        $past = [];
        $dateTime = $this->clock->current()->getAsDateTime();
        $days = $this->getCurrentDays();
        $its = 0;

        while (count($past) < $pastLimit && $its < ($pastLimit * 30) && $days >= 0) {
            $its++;

            if ($this->isItsTimeForScan($days)) {
                $past[] = $dateTime;
                $dateTime = clone $dateTime;
            }
            $dateTime = $dateTime->sub(new \DateInterval('P1D'));
            $days--;
        }

        $future = [];
        $dateTime = $this->clock->current()->getAsDateTime();
        $days = $this->getCurrentDays();
        $its = 0;

        while (count($future) < $futureLimit && $its < ($futureLimit * 30)) {
            if ($days < 0) {
                $dateTime = $this->getStartDate();
                $days = 0;
            } else {
                $dateTime = $dateTime->add(new \DateInterval('P1D'));
                $days++;
            }
            $its++;

            if ($this->isItsTimeForScan($days)) {
                $future[] = $dateTime;
                $dateTime = clone $dateTime;
            }
        }

        $result = array_merge($past, $future);
        sort($result);

        return $result;
    }

    private function getCurrentDays(): int
    {
        $startDate = $this->getStartDate();
        $now = $this->clock->current()->getAsDateTime();

        if ($startDate > $now) {
            return -1;
        }

        return $startDate->diff($now)->days;
    }
}
