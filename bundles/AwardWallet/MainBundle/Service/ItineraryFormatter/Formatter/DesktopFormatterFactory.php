<?php

namespace AwardWallet\MainBundle\Service\ItineraryFormatter\Formatter;

use AwardWallet\MainBundle\Service\ItineraryFormatter\Layer\DesktopCurrentValuesLayer;
use AwardWallet\MainBundle\Service\ItineraryFormatter\Layer\DesktopPreviousValuesLayer;

class DesktopFormatterFactory extends AbstractSimpleFormatterFactory
{
    protected function getCurrentPreviousLayersPair(): array
    {
        return [DesktopCurrentValuesLayer::class, DesktopPreviousValuesLayer::class];
    }
}
