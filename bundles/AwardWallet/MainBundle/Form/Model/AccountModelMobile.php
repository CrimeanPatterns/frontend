<?php

namespace AwardWallet\MainBundle\Form\Model;

class AccountModelMobile extends AccountModel
{
    private CurrencyAndBalanceModel $currencyandbalance;

    public function __construct()
    {
        $this->currencyandbalance = new CurrencyAndBalanceModel();
    }

    public function getCurrencyandbalance(): CurrencyAndBalanceModel
    {
        return $this->currencyandbalance;
    }
}
