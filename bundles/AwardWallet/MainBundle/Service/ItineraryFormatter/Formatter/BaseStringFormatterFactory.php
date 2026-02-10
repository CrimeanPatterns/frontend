<?php

namespace AwardWallet\MainBundle\Service\ItineraryFormatter\Formatter;

use AwardWallet\MainBundle\Service\ItineraryFormatter\Layer\CurrentBaseStringValuesLayer;
use AwardWallet\MainBundle\Service\ItineraryFormatter\Layer\PreviousBaseStringValuesLayer;

class BaseStringFormatterFactory extends AbstractSimpleFormatterFactory
{
    protected function getCurrentPreviousLayersPair(): array
    {
        return [CurrentBaseStringValuesLayer::class, PreviousBaseStringValuesLayer::class];
    }
}
