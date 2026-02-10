<?php

namespace AwardWallet\MainBundle\Service\ItineraryFormatter\Encoder\Factory;

use AwardWallet\MainBundle\Service\ItineraryFormatter\Encoder\CallableEncoder;
use AwardWallet\MainBundle\Service\ItineraryFormatter\Encoder\EncoderInterface;
use AwardWallet\MainBundle\Service\ItineraryFormatter\EncoderContext;

use function AwardWallet\MainBundle\Globals\Utils\iterFluent\it;

class ListMapperEncoderFactory
{
    public function make(EncoderInterface $encoder): EncoderInterface
    {
        return new CallableEncoder(function ($input, EncoderContext $encoderContext) use ($encoder) {
            return
                it($input)
                ->map(function ($listElem) use ($encoder, $encoderContext) {
                    return $encoder->encode($listElem, $encoderContext);
                })
                ->toArrayWithKeys();
        });
    }
}
