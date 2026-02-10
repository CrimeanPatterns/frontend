<?php

namespace AwardWallet\MainBundle\Service\HotelPointValue\RewardSearch;

use AwardWallet\MainBundle\DependencyInjection\Annotation\NoDI;

/**
 * @NoDI()
 */
class HotelItem
{
    private int $hotelId;
    private string $name;
    private string $brandName;
    private float $pointValue;
    private int $avgAboveValue;
    private float $cashPrice;
    private float $pointPrice;
    private string $link;
    private string $location;
    private int $matchCount;

    public function __construct(
        int $hotelId,
        string $name,
        string $brandName,
        float $pointValue,
        int $avgAboveValue,
        float $cashPrice,
        float $pointPrice,
        string $location,
        ?string $link,
        ?int $matchCount
    ) {
        $this->hotelId = $hotelId;
        $this->name = $name;
        $this->brandName = $brandName;
        $this->pointValue = $pointValue;
        $this->avgAboveValue = $avgAboveValue;
        $this->cashPrice = $cashPrice;
        $this->pointPrice = $pointPrice;
        $this->location = $location;
        $this->link = $link ?? '';
        $this->matchCount = $matchCount ?? 0;
    }

    public function getHotelId(): int
    {
        return $this->hotelId;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function getBrandName(): string
    {
        return $this->brandName;
    }

    public function getPointValue(): float
    {
        return $this->pointValue;
    }

    public function getAboveAverage(): int
    {
        return $this->avgAboveValue;
    }

    public function getCashPrice(): int
    {
        return $this->cashPrice;
    }

    public function getPointPrice(): int
    {
        return $this->pointPrice;
    }

    public function getLocation(): string
    {
        return $this->location;
    }

    public function getLink(): string
    {
        return $this->link;
    }

    public function getMatchCount(): int
    {
        return $this->matchCount;
    }
}
