<?php

namespace AwardWallet\MainBundle\Service\AccountHistory\Formatter\MobileHistoryFormatter\Blocks;

class Date
{
    use KindedTrait;

    public $value;

    public function __construct()
    {
        $this->kind = Kind::KIND_DATE;
    }
}
