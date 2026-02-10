<?php

namespace AwardWallet\MainBundle\Service\TravelSummary\Data;

use AwardWallet\MainBundle\DependencyInjection\Annotation\NoDI;

/**
 * @NoDI
 */
class Point implements \JsonSerializable
{
    /**
     * @var float
     */
    private $lat;
    /**
     * @var float
     */
    private $lng;

    public function __construct(float $lat, float $lng)
    {
        $this->lat = $lat;
        $this->lng = $lng;
    }

    public function getLat(): float
    {
        return $this->lat;
    }

    public function getLng(): float
    {
        return $this->lng;
    }

    public function jsonSerialize()
    {
        return [
            'lat' => $this->lat,
            'lng' => $this->lng,
        ];
    }
}
