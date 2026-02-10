<?php

namespace AwardWallet\MainBundle\Service\AccountHistory\Formatter\MobileHistoryFormatter\Blocks;

class EarningPotential extends Block
{
    /**
     * @var string
     */
    public $color;

    /**
     * @var string
     */
    public $type;

    /**
     * @var string
     */
    public $uuid;

    /**
     * @var array
     */
    public $extraData = [];

    public function __construct()
    {
        parent::__construct(Kind::KIND_EARNING_POTENTIAL);
    }
}
