<?php

namespace AwardWallet\MainBundle\Service\OneTimeCodeProcessor\Event;

use AwardWallet\MainBundle\Entity\Account;
use Symfony\Contracts\EventDispatcher\Event;

class AccountSentOTCRecheckEvent extends Event
{
    private Account $account;

    public function __construct(Account $account)
    {
        $this->account = $account;
    }

    public function getAccount(): Account
    {
        return $this->account;
    }
}
