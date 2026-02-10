<?php

namespace AwardWallet\MainBundle\Security\Authenticator\Step;

interface StepFactoryInterface
{
    public function getId(): string;

    public function make(...$args): StepInterface;
}
