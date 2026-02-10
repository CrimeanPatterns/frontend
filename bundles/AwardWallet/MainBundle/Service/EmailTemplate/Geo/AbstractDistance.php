<?php

namespace AwardWallet\MainBundle\Service\EmailTemplate\Geo;

use AwardWallet\MainBundle\DependencyInjection\Annotation\NoDI;

/**
 * @NoDI()
 */
abstract class AbstractDistance implements DistanceInterface
{
    abstract public function getAsMeters(): float;
}
