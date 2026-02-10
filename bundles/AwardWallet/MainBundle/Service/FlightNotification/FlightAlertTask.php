<?php

namespace AwardWallet\MainBundle\Service\FlightNotification;

use AwardWallet\MainBundle\Service\TaskScheduler\Task;

class FlightAlertTask extends Task
{
    private int $segmentId;

    public function __construct(int $segmentId)
    {
        parent::__construct(FlightAlertConsumer::class);

        $this->segmentId = $segmentId;
    }

    public function getSegmentId(): int
    {
        return $this->segmentId;
    }
}
