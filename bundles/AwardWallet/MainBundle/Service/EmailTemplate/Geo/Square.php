<?php

namespace AwardWallet\MainBundle\Service\EmailTemplate\Geo;

use AwardWallet\MainBundle\DependencyInjection\Annotation\NoDI;

/**
 * @NoDI()
 */
class Square implements ShapeInterface
{
    private Point $center;
    private DistanceInterface $side;
    private bool $useZip;
    private bool $useLastLogon;

    public function __construct(
        Point $center,
        DistanceInterface $side,
        bool $useZip = true,
        bool $useLastLogon = true
    ) {
        $this->center = $center;
        $this->side = $side;
        $this->useZip = $useZip;
        $this->useLastLogon = $useLastLogon;
    }

    public function getCenter(): Point
    {
        return $this->center;
    }

    public function getSide(): DistanceInterface
    {
        return $this->side;
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
