<?php

namespace AwardWallet\MainBundle\Service\Billing;

use AwardWallet\MainBundle\Service\TaskScheduler\Task;

class AppleStoreCallbackTask extends Task
{
    private string $receipt;

    public function __construct(string $receipt)
    {
        parent::__construct(AppleStoreCallbackConsumer::class);

        $this->receipt = $receipt;
    }

    public function getReceipt(): string
    {
        return $this->receipt;
    }
}
