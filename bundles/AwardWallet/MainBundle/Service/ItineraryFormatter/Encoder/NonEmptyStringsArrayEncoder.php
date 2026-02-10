<?php

namespace AwardWallet\MainBundle\Service\ItineraryFormatter\Encoder;

use AwardWallet\MainBundle\Service\ItineraryFormatter\EncoderContext;

use function AwardWallet\MainBundle\Globals\Utils\iterFluent\it;

class NonEmptyStringsArrayEncoder extends AbstractBaseEncoder
{
    private NonEmptyStringEncoder $nonEmptyStringEncoder;

    public function __construct(NonEmptyStringEncoder $nonEmptyStringEncoder)
    {
        $this->nonEmptyStringEncoder = $nonEmptyStringEncoder;
    }

    public function encode($input, EncoderContext $encoderContext)
    {
        return
            it($input ?: [])
            ->filter(function ($value) use ($encoderContext) {
                return null !== $this->nonEmptyStringEncoder->encode($value, $encoderContext);
            })
            ->toArrayWithKeys();
    }
}
