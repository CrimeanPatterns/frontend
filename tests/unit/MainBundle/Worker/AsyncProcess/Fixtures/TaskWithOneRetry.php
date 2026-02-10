<?php

namespace AwardWallet\Tests\Unit\MainBundle\Worker\AsyncProcess\Fixtures;

use AwardWallet\MainBundle\Worker\AsyncProcess\Task;

class TaskWithOneRetry extends Task
{
    public function getMaxRetriesCount(): int
    {
        return 1;
    }
}
