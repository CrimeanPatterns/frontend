<?php

namespace AwardWallet\MainBundle\Service\AccountHistory\Formatter\MobileHistoryFormatter\Blocks;

class CashEquivalent extends Block
{
    public bool $isProfit = false;

    public string $pointName;

    public string $diffCashEq;

    public string $currency;

    public string $uuid;

    public array $extraData = [];

    public function __construct()
    {
        parent::__construct(Kind::KIND_CASH_EQUIVALENT);
    }
}
