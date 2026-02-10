<?php

namespace AwardWallet\MainBundle\Loyalty\BackgroundCheck;

use AwardWallet\MainBundle\Worker\AsyncProcess\Task;

class AsyncTask extends Task
{
    public int $providerId;

    public function __construct(int $providerId)
    {
        parent::__construct(AsyncExecutor::class, bin2hex(random_bytes(10)));
        $this->providerId = $providerId;
    }
}
