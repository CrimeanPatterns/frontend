<?php

namespace AwardWallet\MainBundle\Service\AccountHistory\Formatter\MobileHistoryFormatter\Blocks;

class Row
{
    use KindedTrait;

    /**
     * @var Date
     */
    public $date;
    /**
     * @var Block[]
     */
    public $blocks = [];

    /**
     * @var string
     */
    public $style;

    /**
     * @var string
     */
    public $merchant;

    public function __construct()
    {
        $this->kind = Kind::KIND_ROW;
    }
}
