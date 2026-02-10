<?php

namespace AwardWallet\MainBundle\Service\MileValue;

use AwardWallet\MainBundle\DependencyInjection\Annotation\NoDI;
use AwardWallet\MainBundle\Service\MileValue\PriceSource\Price;
use JMS\Serializer\Annotation\Type;

/**
 * @NoDI()
 */
class PriceWithInfo
{
    /**
     * @Type("AwardWallet\MainBundle\Service\MileValue\PriceSource\Price")
     * @var Price
     */
    public $price;
    /**
     * @Type("int")
     * @var int
     */
    public $duration;
    /**
     * @Type("bool")
     * @var bool
     */
    public $lowCoster;

    public function __construct(Price $price, int $duration, bool $lowCoster)
    {
        $this->price = $price;
        $this->duration = $duration;
        $this->lowCoster = $lowCoster;
    }
}
