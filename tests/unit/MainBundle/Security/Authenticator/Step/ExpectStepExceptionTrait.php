<?php

namespace AwardWallet\Tests\Unit\MainBundle\Security\Authenticator\Step;

use AwardWallet\MainBundle\Security\Authenticator\Step\Exception\ErrorStepAuthenticationException;
use AwardWallet\MainBundle\Security\Authenticator\Step\Exception\RequiredStepAuthenticationException;

trait ExpectStepExceptionTrait
{
    protected function expectStepErrorException(string $message): void
    {
        $this->expectException(ErrorStepAuthenticationException::class);
        $this->expectExceptionMessage($message);
    }

    protected function expectStepRequiredException(string $message): void
    {
        $this->expectException(RequiredStepAuthenticationException::class);
        $this->expectExceptionMessage($message);
    }
}
