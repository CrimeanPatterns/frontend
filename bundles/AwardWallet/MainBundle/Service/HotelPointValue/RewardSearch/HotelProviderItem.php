<?php

namespace AwardWallet\MainBundle\Service\HotelPointValue\RewardSearch;

use AwardWallet\MainBundle\DependencyInjection\Annotation\NoDI;
use AwardWallet\MainBundle\Service\MileValue\ProviderMileValueItem;

/**
 * @NoDI()
 */
class HotelProviderItem
{
    private int $providerId;
    private string $brandName;
    private float $avgPointValue;
    private string $formattedAvgPointValue;
    private array $hotels;

    public function __construct(
        int $providerId,
        string $brandName,
        float $avgPointValue,
        array $hotels
    ) {
        $this->providerId = $providerId;
        $this->brandName = $brandName;
        $this->avgPointValue = $avgPointValue;
        $this->hotels = $hotels;

        $this->formattedAvgPointValue = $this->getFormattedAvgPointValue();
    }

    public function getProviderId(): int
    {
        return $this->providerId;
    }

    public function getBrandName(): string
    {
        return $this->brandName;
    }

    public function getAvgPointValue(): float
    {
        return $this->avgPointValue;
    }

    public function getFormattedAvgPointValue(): string
    {
        return $this->avgPointValue . ' ' . ProviderMileValueItem::CURRENCY_CENT;
    }

    public function getHotels(): array
    {
        return $this->hotels;
    }
}
