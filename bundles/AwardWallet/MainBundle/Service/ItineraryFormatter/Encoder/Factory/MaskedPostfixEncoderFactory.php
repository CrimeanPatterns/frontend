<?php

namespace AwardWallet\MainBundle\Service\ItineraryFormatter\Encoder\Factory;

use AwardWallet\MainBundle\Service\ItineraryFormatter\Encoder\CallableEncoder;
use AwardWallet\MainBundle\Service\ItineraryFormatter\Encoder\EncoderInterface;
use AwardWallet\MainBundle\Service\ItineraryFormatter\EncoderContext;

class MaskedPostfixEncoderFactory
{
    public function make(string $placeholder, int $visiblePostfixLength = 4): EncoderInterface
    {
        return new CallableEncoder(function ($input, EncoderContext $encoderContext) use ($placeholder, $visiblePostfixLength) {
            if (\preg_match("/^(\d+)(\d{{$visiblePostfixLength}})$/ims", $input, $match)) {
                return \str_repeat($placeholder, \mb_strlen($match[1])) . $match[2];
            }

            return $input;
        });
    }
}
