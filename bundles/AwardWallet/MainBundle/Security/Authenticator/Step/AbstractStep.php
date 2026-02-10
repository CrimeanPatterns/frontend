<?php

namespace AwardWallet\MainBundle\Security\Authenticator\Step;

use AwardWallet\MainBundle\Security\Authenticator\Credentials;
use AwardWallet\MainBundle\Security\Authenticator\EntryPoint\EntryPointUtils;
use AwardWallet\MainBundle\Security\Authenticator\Step\Exception\ErrorStepAuthenticationException;
use AwardWallet\MainBundle\Security\Authenticator\Step\Exception\RequiredStepAuthenticationException;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Exception\AuthenticationException;

abstract class AbstractStep implements StepInterface
{
    public function getId(): string
    {
        return static::ID;
    }

    public function onFail(Request $request, AuthenticationException $exception): void
    {
    }

    public function onSuccess(Request $request, TokenInterface $token, $providerKey): void
    {
    }

    public function check(Credentials $credentials): bool
    {
        if ($this->supports($credentials)) {
            $this->doCheck($credentials);

            return CheckResult::SUCCESS;
        }

        return CheckResult::ABSTAIN;
    }

    public function otherwise(StepInterface $step): StepInterface
    {
        return new OtherwiseStep($this, $step);
    }

    /**
     * @param null $data
     * @throws ErrorStepAuthenticationException
     */
    protected function throwErrorException(string $message = "", $data = null, int $code = 0, ?\Throwable $previous = null)
    {
        throw new ErrorStepAuthenticationException($this, $data, $message, $code, $previous);
    }

    /**
     * @param null $data
     * @throws RequiredStepAuthenticationException
     */
    protected function throwRequiredException(string $message = "", $data = null, int $code = 0, ?\Throwable $previous = null)
    {
        throw new RequiredStepAuthenticationException($this, $data, $message, $code, $previous);
    }

    protected function getLogContext(Credentials $credentials, array $mixin = []): array
    {
        return EntryPointUtils::getLogContext($credentials, \array_merge(
            $mixin,
            ['auth_step' => static::ID]
        ));
    }

    protected function supports(Credentials $credentials): bool
    {
        return true;
    }

    abstract protected function doCheck(Credentials $credentials): void;
}
