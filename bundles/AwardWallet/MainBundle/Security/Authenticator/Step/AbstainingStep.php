<?php

namespace AwardWallet\MainBundle\Security\Authenticator\Step;

use AwardWallet\MainBundle\Security\Authenticator\Credentials;

class AbstainingStep extends AbstractStep
{
    public const ID = 'abstaining';

    protected function doCheck(Credentials $credentials): void
    {
        // this step should never be reached
        $this->throwErrorException("Invalid credentials");
    }

    protected function supports(Credentials $credentials): bool
    {
        return CheckResult::ABSTAIN;
    }
}
