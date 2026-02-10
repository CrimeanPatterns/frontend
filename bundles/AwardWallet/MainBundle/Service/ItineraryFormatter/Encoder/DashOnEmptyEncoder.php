<?php

namespace AwardWallet\MainBundle\Service\ItineraryFormatter\Encoder;

use AwardWallet\MainBundle\Globals\StringUtils;
use AwardWallet\MainBundle\Service\ItineraryFormatter\EncoderContext;

class DashOnEmptyEncoder extends AbstractBaseEncoder
{
    public function encode($input, EncoderContext $encoderContext)
    {
        if (StringUtils::isEmpty($input)) {
            return '-';
        }

        return $input;
    }
}
