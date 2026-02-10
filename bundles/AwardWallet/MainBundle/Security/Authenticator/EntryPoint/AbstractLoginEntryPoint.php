<?php

namespace AwardWallet\MainBundle\Security\Authenticator\EntryPoint;

use AwardWallet\MainBundle\Security\Authenticator\Step\StepFactoryInterface;
use AwardWallet\MainBundle\Security\Authenticator\Step\StepInterface;
use Psr\Log\LogLevel;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestMatcher;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Exception\AuthenticationException;

use function AwardWallet\MainBundle\Globals\Utils\iterFluent\it;

abstract class AbstractLoginEntryPoint implements LoginEntryPointInterface
{
    /**
     * @var StepInterface[]
     */
    protected $steps;

    /**
     * @var StepFactoryInterface[]
     */
    protected $stepFactories;

    public function onAuthenticationFailure(Request $request, AuthenticationException $exception): ?Response
    {
        foreach ($this->steps as $step) {
            $step->onFail($request, $exception);
        }

        return null;
    }

    public function onAuthenticationSuccess(Request $request, TokenInterface $token, $providerKey): ?Response
    {
        foreach ($this->steps as $step) {
            $step->onSuccess($request, $token, $providerKey);
        }

        return null;
    }

    protected function getLogContext(array $mixin = []): array
    {
        $context = [
            'entry_point' => $this->getId(),
            'aw_server_module' => 'login_form_authenticator',
        ];

        if ($mixin) {
            $context = array_merge($context, $mixin);
        }

        return $context;
    }

    protected function getStep(string $stepId, ...$factoryArgs): StepInterface
    {
        $this->initSteps();

        if (isset($this->steps[$stepId])) {
            return $this->steps[$stepId];
        }

        if (isset($this->stepFactories[$stepId])) {
            return $this->steps[$stepId] = $this->stepFactories[$stepId]->make(...$factoryArgs);
        }

        throw new \RuntimeException("Step '{$stepId}' not found");
    }

    abstract protected function log(string $message, array $context = [], $level = LogLevel::WARNING): void;

    protected function matchRequestByPath(Request $request, string $pathRegexp): bool
    {
        return (new RequestMatcher($pathRegexp))->matches($request);
    }

    protected function matchRequestByRoute(Request $request, string $route): bool
    {
        return $request->attributes->get('_route') === $route;
    }

    private function initSteps()
    {
        if (!is_array($this->steps)) {
            $this->steps =
                it($this->steps)
                ->reindex(function (StepInterface $step) { return $step->getId(); })
                ->toArrayWithKeys();
        }

        if (!is_array($this->stepFactories)) {
            $this->stepFactories =
                it($this->stepFactories)
                ->reindex(function (StepFactoryInterface $stepFactory) { return $stepFactory->getId(); })
                ->toArrayWithKeys();
        }
    }
}
