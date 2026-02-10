<?php

namespace AwardWallet\MainBundle\Loyalty\AccountSaving;

use AwardWallet\MainBundle\Entity\Account;
use Symfony\Contracts\EventDispatcher\Event;

/**
 * Fires when changes are possible in the account, its subaccounts and properties.
 */
class AccountUpdateEvent extends Event
{
    public const SOURCE_ACCOUNT_FORM = 'account_form';
    public const SOURCE_RESTORE_PASSWORD = 'restore_password';
    public const SOURCE_STORE_PASSWORD = 'store_passwords';
    public const SOURCE_ASSIGN_OWNER = 'assign_owner';
    public const SOURCE_SET_GOAL = 'set_goal';
    public const SOURCE_SET_ACTIVE = 'set_active';
    public const SOURCE_CONFIRM_CHANGES = 'confirm_changes';
    public const SOURCE_DISABLE = 'disable';
    public const SOURCE_EMAIL = 'email';
    public const SOURCE_EXTENSION = 'extension';
    public const SOURCE_LOCAL_CHECK = 'local_check';
    public const SOURCE_LOYALTY_CHECK = 'loyalty_check';

    private Account $account;

    private string $source;

    public function __construct(Account $account, string $source)
    {
        $this->account = $account;
        $this->source = $source;
    }

    public function getAccount(): Account
    {
        return $this->account;
    }

    public function getSource(): string
    {
        return $this->source;
    }
}
