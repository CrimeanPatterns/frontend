<?php

namespace AwardWallet\MainBundle\Service\Account\Model\ExpiredAccount;

class Balance
{
    /**
     * @var float
     */
    private $value;

    public function __construct($value)
    {
        $this->value = (float) $value;
    }

    /**
     * @return float
     */
    public function getValue()
    {
        return $this->value;
    }
}
