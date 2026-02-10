<?php

namespace AwardWallet\MainBundle\FrameworkExtension\Listeners\ExternalTracking;

interface ExternalTrackingInterface
{
    public function getData(): array;
}
