<?php

namespace AwardWallet\MainBundle\Service\ItineraryFormatter\Encoder\Factory;

use AwardWallet\MainBundle\Service\ItineraryFormatter\Encoder\CallableEncoder;
use AwardWallet\MainBundle\Service\ItineraryFormatter\EncoderContext;

class ListImplodingEncoderFactory
{
    public function make(string $glue)
    {
        return new CallableEncoder(function ($input, EncoderContext $encoderContext) use ($glue) {
            if (!$input) {
                return null;
            }

            return \implode($glue, $input);
        });
    }
}
