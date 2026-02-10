<?php

namespace AwardWallet\MainBundle\Service\TaskScheduler;

class TaskNeedsRetryException extends \RuntimeException
{
    private int $delay;

    /**
     * @param int $delay Delay in seconds
     */
    public function __construct(int $delay)
    {
        $this->delay = $delay;
    }

    public function getDelay(): int
    {
        return $this->delay;
    }
}
