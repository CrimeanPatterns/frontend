<?php

namespace AwardWallet\MainBundle\Security\Authenticator;

use AwardWallet\MainBundle\Entity\Usr;
use AwardWallet\MainBundle\Security\Authenticator\Step\StepData;
use Symfony\Component\HttpFoundation\Request;

class Credentials
{
    /**
     * @var StepData
     */
    protected $stepData
    ;
    /**
     * @var Request
     */
    protected $request;
    /**
     * @var Usr
     */
    protected $user;
    /**
     * @var string
     */
    private $failedStep;

    public function __construct(StepData $stepData, Request $request)
    {
        $this->stepData = $stepData;
        $this->request = $request;
    }

    public function getStepData(): StepData
    {
        return $this->stepData;
    }

    public function setStepData(StepData $stepData): Credentials
    {
        $this->stepData = $stepData;

        return $this;
    }

    public function getRequest(): Request
    {
        return $this->request;
    }

    public function setRequest(Request $request): Credentials
    {
        $this->request = $request;

        return $this;
    }

    public function getUser(): ?Usr
    {
        return $this->user;
    }

    public function hasUser(): bool
    {
        return (bool) $this->user;
    }

    public function setUser(Usr $user): Credentials
    {
        $this->user = $user;

        return $this;
    }

    public function setFailedStep(string $step): self
    {
        $this->failedStep = $step;

        return $this;
    }

    public function getFailedStep(): ?string
    {
        return $this->failedStep;
    }

    public function isFailed(): bool
    {
        return $this->failedStep !== null;
    }
}
