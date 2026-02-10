<?php

namespace AwardWallet\MainBundle\Service\ItineraryFormatter\Formatter;

use AwardWallet\MainBundle\Service\ItineraryFormatter\Layer\MailCurrentValuesLayer;
use AwardWallet\MainBundle\Service\ItineraryFormatter\Layer\PreviousBaseStringValuesLayer;

class MailFormatterFactory extends AbstractSimpleFormatterFactory
{
    protected function getCurrentPreviousLayersPair(): array
    {
        return [MailCurrentValuesLayer::class, PreviousBaseStringValuesLayer::class];
    }
}
