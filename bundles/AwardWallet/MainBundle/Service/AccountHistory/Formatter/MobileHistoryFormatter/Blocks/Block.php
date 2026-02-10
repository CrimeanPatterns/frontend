<?php

namespace AwardWallet\MainBundle\Service\AccountHistory\Formatter\MobileHistoryFormatter\Blocks;

class Block
{
    use KindedTrait;

    /**
     * @var string
     */
    public $multiplier;

    /**
     * @var string
     */
    public $name;

    /**
     * @var string
     */
    public $value;

    /**
     * @var string
     */
    public $pointsValue;

    /**
     * @var ?array{string, string}
     */
    public ?array $pointsValueRange = null;

    public function __construct(string $kind)
    {
        $this->kind = $kind;
    }
}
