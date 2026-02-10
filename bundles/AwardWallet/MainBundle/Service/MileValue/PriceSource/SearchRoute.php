<?php

namespace AwardWallet\MainBundle\Service\MileValue\PriceSource;

use AwardWallet\MainBundle\DependencyInjection\Annotation\NoDI;

/**
 * @NoDI()
 */
class SearchRoute
{
    /**
     * @var string
     */
    public $depCode;
    /**
     * @var string
     */
    public $arrCode;
    /**
     * @var int
     */
    public $depDate;

    public function __construct(string $depCode, string $arrCode, int $depDate)
    {
        $this->depCode = $depCode;
        $this->arrCode = $arrCode;
        $this->depDate = $depDate;
    }
}
