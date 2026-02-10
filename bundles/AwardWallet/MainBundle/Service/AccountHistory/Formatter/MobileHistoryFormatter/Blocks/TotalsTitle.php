<?php

namespace AwardWallet\MainBundle\Service\AccountHistory\Formatter\MobileHistoryFormatter\Blocks;

class TotalsTitle
{
    use KindedTrait;

    /**
     * @var string
     */
    public $transactions;
    /**
     * @var string
     */
    public $transactionsTitle;
    /**
     * @var string
     */
    public $average;
    /**
     * @var string
     */
    public $averageTitle;
    /**
     * @var string
     */
    public $amount;

    public function __construct()
    {
        $this->kind = Kind::KIND_TOTALS_TITLE;
    }
}
