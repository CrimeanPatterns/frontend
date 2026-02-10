<?php

namespace AwardWallet\MainBundle\Service\ItineraryFormatter\Encoder;

use AwardWallet\MainBundle\Globals\Localizer\LocalizeService;
use AwardWallet\MainBundle\Service\ItineraryFormatter\EncoderContext;

class IntegerEncoder extends AbstractBaseEncoder
{
    private LocalizeService $localizer;

    public function __construct(LocalizeService $localizer)
    {
        $this->localizer = $localizer;
    }

    public function encode($input, EncoderContext $encoderContext)
    {
        if (!\is_numeric($input)) {
            return $input;
        }

        return $this->localizer->formatNumber($input, null, $encoderContext->locale);
    }
}
