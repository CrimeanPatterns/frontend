<?php

namespace AwardWallet\MainBundle\Service\AccountHistory\MerchantRecentTransactionsStatLoader;

use AwardWallet\MainBundle\DependencyInjection\Annotation\NoDI;

/**
 * @NoDI
 */
class Stat
{
    public int $merchantId;
    public int $creditCardId;
    public float $multiplier;
    public int $transactions;

    public function __construct(
        int $merchantId,
        int $creditCardId,
        float $multiplier,
        int $transactions
    ) {
        $this->merchantId = $merchantId;
        $this->creditCardId = $creditCardId;
        $this->multiplier = $multiplier;
        $this->transactions = $transactions;
    }
}
