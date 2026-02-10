<?php

namespace AwardWallet\MainBundle\Service\FlightNotification;

use AwardWallet\MainBundle\Worker\AsyncProcess\Task as BaseAlias;

/**
 * @deprecated use AsyncTask instead
 */
class FlightNotificationTask extends BaseAlias
{
    private int $segmentId;

    public function __construct(int $segmentId)
    {
        parent::__construct(FlightNotificationExecutor::class, bin2hex(random_bytes(10)));

        $this->segmentId = $segmentId;
    }

    public function getSegmentId(): int
    {
        return $this->segmentId;
    }
}
