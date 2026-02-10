<?php

namespace AwardWallet\MainBundle\Event;

use Symfony\Component\EventDispatcher\Event;

/**
 * @deprecated use \AwardWallet\MainBundle\Loyalty\AccountSaving\AccountUpdateEvent and const SOURCE_ACCOUNT_FORM
 */
class AccountFormSavedEvent extends Event
{
    public const NAME = 'aw.account_form_saved';

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
