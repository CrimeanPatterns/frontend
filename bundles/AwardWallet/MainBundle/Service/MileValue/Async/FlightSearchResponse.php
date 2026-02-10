<?php

namespace AwardWallet\MainBundle\Service\MileValue\Async;

use AwardWallet\MainBundle\DependencyInjection\Annotation\NoDI;
use AwardWallet\MainBundle\Service\MileValue\PriceSource\Price;
use AwardWallet\MainBundle\Worker\AsyncProcess\Response;

/**
 * @NoDI
 */
class FlightSearchResponse extends Response
{
    /**
     * @var Price[]
     */
    private $prices;

    /**
     * @param Price[] $prices
     */
    public function __construct(array $prices)
    {
        $this->prices = $prices;
    }

    public function getPrices(): array
    {
        return $this->prices;
    }
}
