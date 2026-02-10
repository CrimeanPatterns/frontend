<?php

namespace AwardWallet\MainBundle\Service\ItineraryFormatter\Encoder\Factory;

use AwardWallet\MainBundle\Globals\Localizer\LocalizeService;
use AwardWallet\MainBundle\Service\ItineraryFormatter\Encoder\CallableEncoder;
use AwardWallet\MainBundle\Service\ItineraryFormatter\Encoder\EncoderInterface;
use AwardWallet\MainBundle\Service\ItineraryFormatter\EncoderContext;

class DecimalEncoderFactory
{
    private LocalizeService $localizeService;

    public function __construct(LocalizeService $localizeService)
    {
        $this->localizeService = $localizeService;
    }

    public function make(int $precision): EncoderInterface
    {
        return new CallableEncoder(function ($value, EncoderContext $encoderContext) use ($precision) {
            if (!\is_numeric($value)) {
                return null;
            }

            return $this->localizeService->formatNumber($value, $precision, $encoderContext->locale);
        });
    }
}
