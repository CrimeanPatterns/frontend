<?php

namespace AwardWallet\MainBundle\Service\MileValue;

use AwardWallet\MainBundle\DependencyInjection\Annotation\NoDI;
use JMS\Serializer\Annotation\Type;

/**
 * @NoDI()
 */
class FoundPrices
{
    public const CHEAPEST_KEY = 'cheapest';
    public const EXACT_MATCH_KEY = 'exactMatch';

    /**
     * @Type("AwardWallet\MainBundle\Service\MileValue\PriceWithInfo")
     * @var PriceWithInfo
     */
    public $cheapest;

    /**
     * @Type("AwardWallet\MainBundle\Service\MileValue\PriceWithInfo")
     * @var PriceWithInfo|null
     */
    public $exactMatch;

    /**
     * @Type("array<AwardWallet\MainBundle\Service\MileValue\PriceWithInfo>")
     * @var PriceWithInfo[]
     */
    public $priceInfos;

    public function __construct(PriceWithInfo $cheapest, ?PriceWithInfo $exactMatch, array $priceInfos)
    {
        $this->cheapest = $cheapest;
        $this->exactMatch = $exactMatch;
        $this->priceInfos = $priceInfos;
    }
}
