<?php

namespace AwardWallet\MainBundle\Worker\AsyncProcess;

use Duration\Duration;

class TaskNeedsRetryException extends \RuntimeException
{
    /**
     * @var int seconds
     */
    private $delay = 0;

    /**
     * @param Duration|int $delay seconds
     */
    public function __construct($delay = 0)
    {
        if ($delay instanceof Duration) {
            $delay = $delay->getAsSecondsInt();
        }

        $this->delay = $delay;
    }

    /**
     * @return int seconds
     */
    public function getDelay(): int
    {
        return $this->delay;
    }
}
