<?php

namespace AwardWallet\MainBundle\Service\EmailTemplate\Geo;

use AwardWallet\MainBundle\DependencyInjection\Annotation\NoDI;

/**
 * @NoDI()
 */
class Circle implements ShapeInterface
{
    private Point $center;
    private DistanceInterface $radius;
    private bool $useZip;
    private bool $useLastLogon;

    public function __construct(
        Point $center,
        DistanceInterface $radius,
        bool $useZip = true,
        bool $useLastLogon = true
    ) {
        $this->center = $center;
        $this->radius = $radius;
        $this->useZip = $useZip;
        $this->useLastLogon = $useLastLogon;
    }

    public function getCenter(): Point
    {
        return $this->center;
    }

    public function getRadius(): DistanceInterface
    {
        return $this->radius;
    }

    public function isUseZip(): bool
    {
        return $this->useZip;
    }

    public function isUseLastLogon(): bool
    {
        return $this->useLastLogon;
    }
}
