<?php

namespace AwardWallet\MainBundle\Service\ItineraryFormatter\Encoder;

use AwardWallet\MainBundle\Service\ItineraryFormatter\EncoderContext;

class TimestampToDateTimeEncoder extends AbstractBaseEncoder
{
    public function encode($input, EncoderContext $encoderContext)
    {
        if (\is_null($input)) {
            return null;
        }

        return new \DateTime('@' . ((int) $input));
    }
}
