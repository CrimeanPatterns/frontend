<?php

namespace AwardWallet\Tests\Unit\MainBundle\Security\Authenticator\Step;

use AwardWallet\MainBundle\Security\Authenticator\Credentials;
use AwardWallet\MainBundle\Security\Authenticator\Step\CallableStep;
use AwardWallet\MainBundle\Security\Authenticator\Step\CheckResult;
use AwardWallet\MainBundle\Security\Authenticator\Step\StepData;
use Codeception\TestCase\Test;
use Prophecy\PhpUnit\ProphecyTrait;
use Symfony\Component\HttpFoundation\Request;

/**
 * @group frontend-unit
 * @group security
 * @coversDefaultClass \AwardWallet\MainBundle\Security\Authenticator\Step\CallableStep
 */
class CallableStepTest extends Test
{
    use ProphecyTrait;

    public function testCredentialsPassedToCallbackOneTimeWithBubblingSuccess()
    {
        $callbackInvocationCount = 0;
        $credentials = new Credentials(new StepData(), new Request());
        $step = new CallableStep(function ($passedCredentials) use (&$callbackInvocationCount, $credentials) {
            $callbackInvocationCount++;
            $this->assertSame($credentials, $passedCredentials);

            return true;
        });
        $this->assertEquals(CheckResult::SUCCESS, $step->check($credentials));
        $this->assertEquals(1, $callbackInvocationCount);
    }

    public function testBubblingFalseResult()
    {
        $callbackInvocationCount = 0;
        $credentials = new Credentials(new StepData(), new Request());
        $step = new CallableStep(function ($passedCredentials) use (&$callbackInvocationCount) {
            $callbackInvocationCount++;

            return false;
        });
        $this->assertEquals(CheckResult::ABSTAIN, $step->check($credentials));
        $this->assertEquals(1, $callbackInvocationCount);
    }

    public function testExceptionThrownInCallbackIsNotSuppressed()
    {
        $callbackInvocationCount = 0;
        $exception = new \RuntimeException('bla-bla');
        $credentials = new Credentials(new StepData(), new Request());
        $step = new CallableStep(function () use (&$callbackInvocationCount, $exception) {
            $callbackInvocationCount++;

            throw $exception;
        });

        try {
            $step->check($credentials);
            $this->fail('should not executing here!');
        } catch (\Throwable $catched) {
            $this->assertSame($exception, $catched);
        }

        $this->assertEquals(1, $callbackInvocationCount);
    }
}
