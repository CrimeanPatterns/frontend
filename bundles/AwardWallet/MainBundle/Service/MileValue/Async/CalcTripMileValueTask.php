<?php

namespace AwardWallet\MainBundle\Service\MileValue\Async;

use AwardWallet\MainBundle\Worker\AsyncProcess\Task;

class CalcTripMileValueTask extends Task
{
    private int $tripId;

    public function __construct(int $tripId)
    {
        parent::__construct(CalcTripMileValueExecutor::class, bin2hex(random_bytes(10)));
        $this->tripId = $tripId;
    }

    public function getTripId(): int
    {
        return $this->tripId;
    }
}
