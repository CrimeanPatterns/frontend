<?php

namespace AwardWallet\MainBundle\Service\ItineraryFormatter\Encoder;

use AwardWallet\MainBundle\Service\ItineraryFormatter\EncoderContext;

/**
 * @template Input
 * @template Output
 */
interface EncoderInterface
{
    /**
     * @param Input $input
     * @return ?Output
     */
    public function __apply($input, EncoderContext $encoderContext);

    /**
     * @param Input $input
     * @return ?Output
     */
    public function encode($input, EncoderContext $encoderContext);

    public function andThen(EncoderInterface $encoder): EncoderInterface;

    public function andThenIfExists(EncoderInterface $encoder): EncoderInterface;

    public function andThenIfNotEmpty(EncoderInterface $encoder): EncoderInterface;
}
