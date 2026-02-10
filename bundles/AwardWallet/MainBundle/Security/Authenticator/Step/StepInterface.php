<?php

namespace AwardWallet\MainBundle\Security\Authenticator\Step;

use AwardWallet\MainBundle\Security\Authenticator\Credentials;
use AwardWallet\MainBundle\Security\Authenticator\Step\Exception\ErrorStepAuthenticationException;
use AwardWallet\MainBundle\Security\Authenticator\Step\Exception\RequiredStepAuthenticationException;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Exception\AuthenticationException;

interface StepInterface
{
    /**
     * @return bool - false: step abstain from check
     * @throws ErrorStepAuthenticationException|RequiredStepAuthenticationException
     */
    public function check(Credentials $credentials): bool;

    public function getId(): string;

    public function onFail(Request $request, AuthenticationException $exception): void;

    public function onSuccess(Request $request, TokenInterface $token, $providerKey): void;

    public function otherwise(StepInterface $step): StepInterface;
}
