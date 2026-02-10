<?php

namespace AwardWallet\MainBundle\Service\TravelSummary\Data;

use AwardWallet\MainBundle\DependencyInjection\Annotation\NoDI;

/**
 * @NoDI()
 */
class DistanceResult
{
    /**
     * Distance traveled.
     */
    private int $distance;
    /**
     * Times around the world.
     */
    private float $aroundTheWorld;

    public function __construct(int $distance, float $aroundTheWorld)
    {
        $this->distance = $distance;
        $this->aroundTheWorld = $aroundTheWorld;
    }

    public function getDistance(): int
    {
        return $this->distance;
    }

    public function getAroundTheWorld(): float
    {
        return $this->aroundTheWorld;
    }
}
