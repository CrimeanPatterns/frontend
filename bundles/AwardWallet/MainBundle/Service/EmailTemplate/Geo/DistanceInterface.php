<?php

namespace AwardWallet\MainBundle\Service\EmailTemplate\Geo;

interface DistanceInterface
{
    public function getAsMeters(): float;
}
