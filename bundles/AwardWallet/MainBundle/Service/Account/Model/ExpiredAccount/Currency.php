<?php

namespace AwardWallet\MainBundle\Service\Account\Model\ExpiredAccount;

use AwardWallet\MainBundle\Entity\Currency as CurrencyEntity;

class Currency
{
    /**
     * @var int
     */
    private $amount;

    /**
     * @var CurrencyEntity
     */
    private $value;

    public function __construct(int $amount, CurrencyEntity $value)
    {
        $this->amount = $amount;
        $this->value = $value;
    }

    public function getAmount(): int
    {
        return $this->amount;
    }

    public function getValue(): CurrencyEntity
    {
        return $this->value;
    }
}
