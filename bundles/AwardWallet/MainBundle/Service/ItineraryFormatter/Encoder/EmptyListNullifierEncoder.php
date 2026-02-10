<?php

namespace AwardWallet\MainBundle\Service\ItineraryFormatter\Encoder;

use AwardWallet\MainBundle\Service\ItineraryFormatter\EncoderContext;

class EmptyListNullifierEncoder extends AbstractBaseEncoder
{
    public function encode($input, EncoderContext $encoderContext)
    {
        if (\is_array($input) && !$input) {
            return null;
        }

        return $input;
    }
}
