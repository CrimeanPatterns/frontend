<?php

namespace AwardWallet\MainBundle\Service\EmailTemplate\Geo;

use AwardWallet\MainBundle\DependencyInjection\Annotation\NoDI;

/**
 * @NoDI()
 */
class Kilometers extends AbstractDistance
{
    private float $kilometers;

    public function __construct(float $kilometers)
    {
        $this->kilometers = $kilometers;
    }

    public function getAsMeters(): float
    {
        return $this->kilometers * 1000;
    }
}
