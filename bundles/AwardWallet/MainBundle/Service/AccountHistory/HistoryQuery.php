<?php

namespace AwardWallet\MainBundle\Service\AccountHistory;

use AwardWallet\MainBundle\DependencyInjection\Annotation\NoDI;
use AwardWallet\MainBundle\Entity\Account;
use AwardWallet\MainBundle\Entity\Subaccount;
use AwardWallet\MainBundle\Service\AccountHistory\Formatter\HistoryFormatterInterface;

/**
 * @NoDI()
 */
class HistoryQuery extends AbstractAccountHistoryQuery
{
    /** @var HistoryFormatterInterface */
    private $formatter;
    /** @var Account */
    private $account;
    /** @var Subaccount */
    private $subAccount;

    public function __construct(Account $account, ?string $descriptionFilter = null, ?NextPageToken $nextPageToken = null)
    {
        $this->account = $account;
        $this->descriptionFilter = $descriptionFilter;
        $this->nextPageToken = $nextPageToken;
    }

    /**
     * @return $this
     */
    public function setSubAccount(?Subaccount $subAccount)
    {
        $this->subAccount = $subAccount;

        return $this;
    }

    /**
     * @return $this
     */
    public function setFormatter(HistoryFormatterInterface $formatter)
    {
        $this->formatter = $formatter;

        return $this;
    }

    public function getFormatter(): ?HistoryFormatterInterface
    {
        return $this->formatter;
    }

    public function getAccount(): Account
    {
        return $this->account;
    }

    public function getSubAccount(): ?Subaccount
    {
        return $this->subAccount;
    }
}
