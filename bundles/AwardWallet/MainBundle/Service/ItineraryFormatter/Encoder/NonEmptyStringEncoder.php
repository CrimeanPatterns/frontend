<?php

namespace AwardWallet\MainBundle\Service\ItineraryFormatter\Encoder;

use AwardWallet\MainBundle\Globals\StringUtils;
use AwardWallet\MainBundle\Service\ItineraryFormatter\Encoder\Exception\EncoderException;
use AwardWallet\MainBundle\Service\ItineraryFormatter\EncoderContext;

class NonEmptyStringEncoder extends AbstractBaseEncoder
{
    public function encode($input, EncoderContext $encoderContext)
    {
        if (\is_null($input)) {
            return null;
        }

        if (\is_string($input)) {
            $input = \trim($input);

            return StringUtils::isNotEmpty($input) ? $input : null;
        }

        if (\is_scalar($input)) {
            return $input;
        }

        throw new EncoderException(\sprintf("%s expected scalar input", NonEmptyStringEncoder::class));
    }
}
