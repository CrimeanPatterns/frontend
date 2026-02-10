<?php

namespace AwardWallet\MainBundle\Event;

use AwardWallet\MainBundle\Entity\Account;
use AwardWallet\MainBundle\Loyalty\AccountSaving\ProcessingReport;
use AwardWallet\MainBundle\Loyalty\Resources\CheckAccountResponse;
use Symfony\Component\EventDispatcher\Event;

/**
 * @deprecated use \AwardWallet\MainBundle\Loyalty\AccountSaving\AccountUpdateEvent
 * does not work in actions on the list of accounts, emails, account editing form
 */
class AccountUpdatedEvent extends Event
{
    public const NAME = 'aw.account_updated';
    public const UPDATE_METHOD_LOYALTY = 1;
    public const UPDATE_METHOD_EXTENSION = 2;

    /** @var Account */
    private $account;

    /** @var ProcessingReport */
    private $saveReport;

    /** @var CheckAccountResponse */
    private $checkAccountResponse;

    private $updateMethod;

    public function __construct(Account $account, CheckAccountResponse $checkAccountResponse, ProcessingReport $saveReport, int $updateMethod)
    {
        $this->account = $account;
        $this->saveReport = $saveReport;
        $this->checkAccountResponse = $checkAccountResponse;
        $this->updateMethod = $updateMethod;
    }

    public function getCheckAccountResponse(): CheckAccountResponse
    {
        return $this->checkAccountResponse;
    }

    public function getAccount(): Account
    {
        return $this->account;
    }

    public function getSaveReport(): ProcessingReport
    {
        return $this->saveReport;
    }

    public function getUpdateMethod(): int
    {
        return $this->updateMethod;
    }
}
