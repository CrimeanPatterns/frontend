<?php

namespace AwardWallet\MainBundle\Worker\AsyncProcess;

use AwardWallet\MainBundle\Globals\StringUtils;

class UpdaterAccountTickTask extends Task
{
    /**
     * @var int
     */
    public $accountId;

    public function __construct(int $accountId)
    {
        parent::__construct(UpdaterTaskExecutor::class, StringUtils::getRandomCode(20));

        $this->accountId = $accountId;
    }

    public function getMaxRetriesCount(): int
    {
        return 7;
    }
}
