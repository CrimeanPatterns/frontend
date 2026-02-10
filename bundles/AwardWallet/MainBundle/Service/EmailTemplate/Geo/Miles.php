<?php

namespace AwardWallet\MainBundle\Service\EmailTemplate\Geo;

use AwardWallet\MainBundle\DependencyInjection\Annotation\NoDI;

/**
 * @NoDI()
 */
class Miles extends AbstractDistance
{
    private float $miles;

    public function __construct(float $miles)
    {
        $this->miles = $miles;
    }

    public function getAsMeters(): float
    {
        return $this->miles * 1609.344;
    }

    public function getMiles(): float
    {
        return $this->miles;
    }
}
