<?php

namespace AwardWallet\MainBundle\Event;

use AwardWallet\MainBundle\Entity\Account;
use AwardWallet\MainBundle\Entity\Subaccount;
use Symfony\Contracts\EventDispatcher\Event;

class AccountBalanceChangedEvent extends Event
{
    public const SOURCE_MANUAL = -1;

    private Account $account;

    /**
     * @var Subaccount[]
     */
    private array $subAccounts;

    /**
     * one of UpdaterEngineInterface::SOURCE_ constants or self::SOURCE_MANUAL (account form).
     */
    private ?int $source;

    /**
     * false - was changed only balance of subaccount(s).
     */
    private bool $accountChanged;

    /**
     * @param Subaccount[] $changedSubAccounts
     */
    public function __construct(Account $account, array $changedSubAccounts = [], ?int $source = null, bool $accountChanged = true)
    {
        $this->account = $account;
        $this->subAccounts = $changedSubAccounts;
        $this->source = $source;
        $this->accountChanged = $accountChanged;
    }

    public function getAccount(): Account
    {
        return $this->account;
    }

    /**
     * @return Subaccount[]
     */
    public function getSubAccounts(): array
    {
        return $this->subAccounts;
    }

    public function getSource(): ?int
    {
        return $this->source;
    }

    public function isAccountChanged(): bool
    {
        return $this->accountChanged;
    }
}
