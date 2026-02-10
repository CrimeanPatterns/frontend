<?php

namespace AwardWallet\MainBundle\Service\BalanceWatch\Model;

use AwardWallet\MainBundle\Globals\StringUtils;
use AwardWallet\MainBundle\Service\BalanceWatch\BalanceWatchExecutor;
use AwardWallet\MainBundle\Worker\AsyncProcess\Task;

class BalanceWatchTask extends Task
{
    /** @var int */
    public $accountId;

    public function __construct(int $accountId)
    {
        parent::__construct(BalanceWatchExecutor::class, StringUtils::getRandomCode(20));
        $this->accountId = $accountId;
    }
}
