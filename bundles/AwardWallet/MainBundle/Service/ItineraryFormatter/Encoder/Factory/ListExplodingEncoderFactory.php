<?php

namespace AwardWallet\MainBundle\Service\ItineraryFormatter\Encoder\Factory;

use AwardWallet\MainBundle\Globals\StringUtils;
use AwardWallet\MainBundle\Service\ItineraryFormatter\Encoder\CallableEncoder;
use AwardWallet\MainBundle\Service\ItineraryFormatter\EncoderContext;

class ListExplodingEncoderFactory
{
    public function make(string $glue)
    {
        return new CallableEncoder(function ($input, EncoderContext $encoderContext) use ($glue) {
            if (StringUtils::isEmpty($input)) {
                return null;
            }

            return \explode($glue, $input);
        });
    }
}
