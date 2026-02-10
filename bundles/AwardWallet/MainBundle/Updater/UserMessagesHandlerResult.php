<?php

namespace AwardWallet\MainBundle\Updater;

class UserMessagesHandlerResult
{
    /**
     * @var list<AddAccount>|list<int>
     */
    public array $addAccounts = [];
    /**
     * @var list<int>
     */
    public array $removeAccounts = [];
    /**
     * @var list<int>
     */
    public array $refuseLocalPasswords = [];
    public bool $unpause = false;
}
