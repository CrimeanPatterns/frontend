<?php

namespace AwardWallet\MainBundle\Service\ItineraryFormatter\Encoder;

use AwardWallet\MainBundle\Globals\StringUtils;
use AwardWallet\MainBundle\Service\ItineraryFormatter\EncoderContext;

/**
 * @template Input
 * @template Output
 * @template-implements EncoderInterface<Input, Output>
 */
abstract class AbstractBaseEncoder implements EncoderInterface
{
    /**
     * @param Input $input
     * @return ?Output
     */
    public function __apply($input, EncoderContext $encoderContext)
    {
        return $this->encode($input, $encoderContext);
    }

    public function andThen(EncoderInterface $encoder): EncoderInterface
    {
        return new CallableEncoder(function ($input, EncoderContext $encoderContext) use ($encoder) {
            $output = $this->encode($input, $encoderContext);

            return $encoder->encode($output, $encoderContext);
        });
    }

    public function andThenIfExists(EncoderInterface $encoder): EncoderInterface
    {
        return new CallableEncoder(function ($input, EncoderContext $encoderContext) use ($encoder) {
            $output = $this->encode($input, $encoderContext);

            if (\is_null($output)) {
                return null;
            }

            return $encoder->encode($output, $encoderContext);
        });
    }

    public function andThenIfNotEmpty(EncoderInterface $encoder): EncoderInterface
    {
        return new CallableEncoder(function ($input, EncoderContext $encoderContext) use ($encoder) {
            $output = $this->encode($input, $encoderContext);

            if (
                \is_null($output)
                || (
                    \is_scalar($output)
                    && StringUtils::isEmpty($output)
                )
            ) {
                return null;
            } elseif (
                \is_array($output)
                && !$output
            ) {
                return null;
            }

            return $encoder->encode($output, $encoderContext);
        });
    }
}
