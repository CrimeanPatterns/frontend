<?php

namespace AwardWallet\MainBundle\Worker\AsyncProcess;

use AwardWallet\MainBundle\Globals\StringUtils;
use AwardWallet\MainBundle\Updater\AddAccount;
use AwardWallet\MainBundle\Updater\UserMessagesHandlerResult;

class UpdaterSessionTickTask extends Task
{
    /**
     * @var string
     */
    public $sessionKey;
    /**
     * @var AddAccount[]
     */
    public $addAccounts = [];
    /**
     * @var list<int>
     */
    public $removeAccounts = [];
    /**
     * @var string
     */
    public $serializedHttpRequest;
    public ?UserMessagesHandlerResult $userMessagesHandlerResult = null;

    public function __construct(string $sessionKey, ?UserMessagesHandlerResult $userMessagesHandlerResult = null, ?string $serializedHttpRequest = null)
    {
        parent::__construct(UpdaterTaskExecutor::class, StringUtils::getRandomCode(20));

        $this->sessionKey = $sessionKey;
        $this->userMessagesHandlerResult = $userMessagesHandlerResult;
        $this->serializedHttpRequest = $serializedHttpRequest;
    }

    public function getMaxRetriesCount(): int
    {
        return 30;
    }
}
