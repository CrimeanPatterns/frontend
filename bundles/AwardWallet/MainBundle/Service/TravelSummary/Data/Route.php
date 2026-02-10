<?php

namespace AwardWallet\MainBundle\Service\TravelSummary\Data;

use AwardWallet\MainBundle\DependencyInjection\Annotation\NoDI;

/**
 * @NoDI
 */
class Route implements \JsonSerializable
{
    /**
     * @var Point
     */
    private $dep;
    /**
     * @var Point
     */
    private $arr;
    /**
     * @var Point[] waypoints for reservations from multiple segments
     */
    private array $waypoints = [];

    public function __construct(Point $dep, Point $arr)
    {
        $this->dep = $dep;
        $this->arr = $arr;
    }

    public function getDep(): Point
    {
        return $this->dep;
    }

    public function getArr(): Point
    {
        return $this->arr;
    }

    public function setArr(Point $arr): self
    {
        $this->arr = $arr;

        return $this;
    }

    public function getWaypoints(): array
    {
        return $this->waypoints;
    }

    public function addWaypoint(Point $point): self
    {
        $this->waypoints[] = $point;

        return $this;
    }

    public function jsonSerialize()
    {
        return [
            'dep' => $this->dep,
            'arr' => $this->arr,
            'waypoints' => $this->waypoints,
        ];
    }
}
