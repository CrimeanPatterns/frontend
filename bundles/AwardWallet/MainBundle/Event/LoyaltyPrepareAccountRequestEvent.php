<?php

namespace AwardWallet\MainBundle\Event;

use AwardWallet\MainBundle\Entity\Account;
use AwardWallet\MainBundle\Loyalty\Resources\CheckAccountRequest;
use Symfony\Component\EventDispatcher\Event;

class LoyaltyPrepareAccountRequestEvent extends Event
{
    public const NAME = 'aw.loyalty.prepare_account_request';

    /** @var Account */
    private $account;
    /** @var CheckAccountRequest */
    private $request;

    public function __construct(Account $account, CheckAccountRequest $request)
    {
        $this->account = $account;
        $this->request = $request;
    }

    public function getAccount(): Account
    {
        return $this->account;
    }

    public function getRequest(): CheckAccountRequest
    {
        return $this->request;
    }
}
