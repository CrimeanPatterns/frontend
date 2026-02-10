<?php

namespace AwardWallet\MainBundle\Event;

use Symfony\Component\EventDispatcher\Event;

/**
 * @deprecated use \AwardWallet\MainBundle\Loyalty\AccountSaving\AccountUpdateEvent
 * fires only on the list of accounts and the account editing form. Does not contain information about the source of the changes
 */
class AccountChangedEvent extends Event
{
    public const NAME = 'aw.account_changed';

    private $accountId;

    public function __construct($accountId)
    {
        $this->accountId = $accountId;
    }

    public function getAccountId()
    {
        return $this->accountId;
    }
}
