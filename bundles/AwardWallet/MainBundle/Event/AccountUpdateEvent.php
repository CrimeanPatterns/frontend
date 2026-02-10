<?php

namespace AwardWallet\MainBundle\Event;

use AwardWallet\MainBundle\Entity\Account;
use AwardWallet\MainBundle\Entity\Subaccount;
use Symfony\Component\EventDispatcher\Event;

/**
 * @deprecated use \AwardWallet\MainBundle\Loyalty\AccountSaving\AccountUpdateEvent
 */
class AccountUpdateEvent extends Event
{
    /**
     * @var array
     */
    private $accountInfo;
    /**
     * @var Account
     */
    private $account;
    /**
     * @var \AccountCheckReport
     */
    private $report;
    /**
     * @var Subaccount[]
     */
    private $subAcccounts;

    public function __construct(array $accountInfo, Account $account, \AccountCheckReport $report, array $subAcccounts)
    {
        $this->accountInfo = $accountInfo;
        $this->account = $account;
        $this->report = $report;

        $subAcccountsMap = [];

        /** @var Subaccount $subAcccount */
        foreach ($subAcccounts as $subAcccount) {
            $subAcccountsMap[$subAcccount->getAccountid()->getAccountid() . '_' . $subAcccount->getCode()] = $subAcccount;
        }
        $this->subAcccounts = $subAcccountsMap;
    }

    /**
     * @return Subaccount[]
     */
    public function getSubAcccounts()
    {
        return $this->subAcccounts;
    }

    /**
     * @return \AccountCheckReport
     */
    public function getReport()
    {
        return $this->report;
    }

    /**
     * @return array
     */
    public function getAccountInfo()
    {
        return $this->accountInfo;
    }

    /**
     * @return Account
     */
    public function getAccount()
    {
        return $this->account;
    }
}
