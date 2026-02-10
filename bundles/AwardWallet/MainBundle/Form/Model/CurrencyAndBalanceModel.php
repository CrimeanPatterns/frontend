<?php

namespace AwardWallet\MainBundle\Form\Model;

class CurrencyAndBalanceModel
{
    private $currency;
    private $balance;

    public function getCurrency()
    {
        return $this->currency;
    }

    public function setCurrency($currency)
    {
        $this->currency = $currency;

        return $this;
    }

    public function getBalance()
    {
        return $this->balance;
    }

    public function setBalance($balance)
    {
        $this->balance = $balance;

        return $this;
    }
}
