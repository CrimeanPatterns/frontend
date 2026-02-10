<?php

namespace AwardWallet\MainBundle\Timeline\Formatter\Mobile\Formatted\Components\AlternativeFlights;

use AwardWallet\MainBundle\Service\MileValue\FoundPrices;

class Choice
{
    public const TYPE_CUSTOM = 'custom';
    public const TYPE_CHEAPEST = FoundPrices::CHEAPEST_KEY;
    public const TYPE_YOUR_FLIGHT = FoundPrices::EXACT_MATCH_KEY;

    public int $value;
    public ?string $airline;
    public ?string $price;
    public string $type;
    /**
     * @var Block[]
     */
    public array $blocks;

    public function __construct(int $choice, string $type, ?string $airline = null, ?string $price = null)
    {
        $this->value = $choice;
        $this->airline = $airline;
        $this->price = $price;
        $this->type = $type;
    }
}
