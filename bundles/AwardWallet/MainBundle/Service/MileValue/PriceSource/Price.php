<?php

namespace AwardWallet\MainBundle\Service\MileValue\PriceSource;

use AwardWallet\MainBundle\DependencyInjection\Annotation\NoDI;
use JMS\Serializer\Annotation\Type;

/**
 * @NoDI()
 */
class Price
{
    /**
     * @Type("float")
     * @var float - total price for all passengers
     */
    public $price;
    /**
     * @Type("float")
     * @var float - adjustments made to price from original price source
     */
    public $priceAdjustment;
    /**
     * @Type("array<AwardWallet\MainBundle\Service\MileValue\PriceSource\ResultRoute>")
     * @var ResultRoute[]
     */
    public $routes;
    /**
     * @Type("string")
     * @var string - price source id
     */
    public $source;
    /**
     * @Type("string")
     * @var string|null
     */
    public $bookingURL;
    /**
     * @Type("array")
     * @var array|null
     */
    public $rawData;

    /**
     * @param float $price - total price for all passengers
     * @param ResultRoute[] $routes
     */
    public function __construct(string $source, float $price, array $routes, ?string $bookingURL, ?array $rawData = null)
    {
        $this->source = $source;
        $this->price = $price;
        $this->routes = $routes;
        $this->bookingURL = $bookingURL;
        $this->rawData = $rawData;
    }
}
