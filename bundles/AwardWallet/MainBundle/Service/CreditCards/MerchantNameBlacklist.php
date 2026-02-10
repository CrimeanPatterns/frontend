<?php

namespace AwardWallet\MainBundle\Service\CreditCards;

class MerchantNameBlacklist
{
    /**
     * expects UPPERCASE.
     */
    public function isBlacklisted(string $name): bool
    {
        return
            $name === 'PURCHASE ADJUSTMENT'
            || $name === 'FOREIGN TRANSACTION FEE'
            || $name === 'CREDIT ADJUSTMENT'
            || $name === 'CREDIT BALANCE REFUND'
            || $name === 'CREDIT BALANCE REFUND DEBIT'
            || $name === 'CREDIT CASH BACK REWARD'
            || $name === 'CREDIT DISTRIBUTION OF PAYMENT'
            || $name === 'CREDIT FOR AMEX ERROR'
            || $name === 'CREDIT MISPOSTED PYMT'
            || $name === 'CREDIT REFUND AS REQUESTED'
        ;
    }
}
