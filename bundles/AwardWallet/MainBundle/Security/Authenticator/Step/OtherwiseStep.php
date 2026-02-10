<?php

namespace AwardWallet\MainBundle\Security\Authenticator\Step;

use AwardWallet\MainBundle\Security\Authenticator\Credentials;

class OtherwiseStep extends AbstractStep
{
    /**
     * @var StepInterface
     */
    private $fallbackStep;
    /**
     * @var StepInterface
     */
    private $mainStep;

    public function __construct(StepInterface $mainStep, StepInterface $fallbackStep)
    {
        $this->fallbackStep = $fallbackStep;
        $this->mainStep = $mainStep;
    }

    public function check(Credentials $credentials): bool
    {
        if (CheckResult::SUCCESS === $this->mainStep->check($credentials)) {
            return CheckResult::SUCCESS;
        }

        return $this->fallbackStep->check($credentials);
    }

    protected function doCheck(Credentials $credentials): void
    {
    }
}
