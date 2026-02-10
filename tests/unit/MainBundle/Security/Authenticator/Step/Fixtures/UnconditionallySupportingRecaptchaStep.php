<?php

namespace AwardWallet\Tests\Unit\MainBundle\Security\Authenticator\Step\Fixtures;

use AwardWallet\MainBundle\Security\Authenticator\Credentials;
use AwardWallet\MainBundle\Security\Authenticator\Step\AbstractStep;

class UnconditionallySupportingRecaptchaStep extends AbstractStep
{
    public const ID = 'unconditionally_step';

    protected function doCheck(Credentials $credentials): void
    {
    }
}
