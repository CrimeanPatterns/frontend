<?php

namespace AwardWallet\MainBundle\Service\ItineraryFormatter\Encoder;

use AwardWallet\MainBundle\DependencyInjection\Annotation\NoDI;
use AwardWallet\MainBundle\Service\ItineraryFormatter\EncoderContext;

/**
 * @NoDI()
 * @template Input
 * @template Output
 */
class CallableEncoder extends AbstractBaseEncoder
{
    /**
     * @var callable(Input): ?Output
     */
    private $callable;

    /**
     * @param callable(Input): ?Output $callable
     */
    public function __construct(callable $callable)
    {
        $this->callable = $callable;
    }

    /**
     * @param Input $input
     * @return ?Output
     */
    public function encode($input, EncoderContext $encoderContext)
    {
        return ($this->callable)($input, $encoderContext);
    }

    /**
     * @template InputNew
     * @template OutputNew
     * @param callable(InputNew): ?OutputNew $callable
     * @return self<InputNew, OutputNew>
     */
    public static function new(callable $callable): self
    {
        return new self($callable);
    }
}
