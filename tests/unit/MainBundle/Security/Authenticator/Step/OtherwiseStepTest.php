<?php

namespace AwardWallet\Tests\Unit\MainBundle\Security\Authenticator\Step;

use AwardWallet\MainBundle\Security\Authenticator\Credentials;
use AwardWallet\MainBundle\Security\Authenticator\Step\CallableStep;
use AwardWallet\MainBundle\Security\Authenticator\Step\CheckResult;
use AwardWallet\MainBundle\Security\Authenticator\Step\OtherwiseStep;
use AwardWallet\MainBundle\Security\Authenticator\Step\StepData;
use Codeception\TestCase\Test;
use Prophecy\PhpUnit\ProphecyTrait;
use Symfony\Component\HttpFoundation\Request;

/**
 * @coversDefaultClass \AwardWallet\MainBundle\Security\Authenticator\Step\OtherwiseStep
 * @group frontend-unit
 * @group security
 */
class OtherwiseStepTest extends Test
{
    use ProphecyTrait;

    public function testMainStepSuccessFastReturn()
    {
        $credentials = new Credentials(new StepData(), new Request());
        $mainStepInvocationCount = 0;
        $mainStep = new CallableStep(function (Credentials $passedCredentials) use (&$mainStepInvocationCount, &$credentials) {
            $this->assertSame($credentials, $passedCredentials);
            $mainStepInvocationCount++;

            return CheckResult::SUCCESS;
        });

        $fallbackStepInvocationCount = 0;
        $fallbackStep = new CallableStep(function (Credentials $credentials) use (&$fallbackStepInvocationCount) {
            $fallbackStepInvocationCount++;
        });

        $otherwise = new OtherwiseStep($mainStep, $fallbackStep);
        $this->assertEquals(CheckResult::SUCCESS, $otherwise->check($credentials));
        $this->assertEquals(1, $mainStepInvocationCount);
        $this->assertEquals(0, $fallbackStepInvocationCount);
    }

    public function testFallbackStepSuccessResultReturn()
    {
        $credentials = new Credentials(new StepData(), new Request());
        $mainStepInvocationCount = 0;
        $mainStep = new CallableStep(function (Credentials $passedCredentials) use (&$mainStepInvocationCount) {
            $mainStepInvocationCount++;

            return CheckResult::ABSTAIN;
        });

        $fallbackStepInvocationCount = 0;
        $fallbackStep = new CallableStep(function (Credentials $passedCredentials) use (&$fallbackStepInvocationCount, $credentials) {
            $this->assertSame($credentials, $passedCredentials);
            $fallbackStepInvocationCount++;

            return CheckResult::SUCCESS;
        });

        $otherwise = new OtherwiseStep($mainStep, $fallbackStep);
        $this->assertEquals(CheckResult::SUCCESS, $otherwise->check($credentials));
        $this->assertEquals(1, $mainStepInvocationCount);
        $this->assertEquals(1, $fallbackStepInvocationCount);
    }

    public function testFallbackStepAbstainResultReturn()
    {
        $mainStepInvocationCount = 0;
        $mainStep = new CallableStep(function (Credentials $passedCredentials) use (&$mainStepInvocationCount) {
            $mainStepInvocationCount++;

            return CheckResult::ABSTAIN;
        });

        $fallbackStepInvocationCount = 0;
        $fallbackStep = new CallableStep(function (Credentials $passedCredentials) use (&$fallbackStepInvocationCount) {
            $fallbackStepInvocationCount++;

            return CheckResult::ABSTAIN;
        });

        $otherwise = new OtherwiseStep($mainStep, $fallbackStep);
        $this->assertEquals(CheckResult::ABSTAIN, $otherwise->check(new Credentials(new StepData(), new Request())));
        $this->assertEquals(1, $mainStepInvocationCount);
        $this->assertEquals(1, $fallbackStepInvocationCount);
    }

    public function testMainStepFail()
    {
        $mainStepInvocationCount = 0;
        $exception = new \RuntimeException('bla-bla');
        $mainStep = new CallableStep(function (Credentials $passedCredentials) use (&$mainStepInvocationCount, $exception) {
            $mainStepInvocationCount++;

            throw $exception;
        });

        $fallbackStepInvocationCount = 0;
        $fallbackStep = new CallableStep(function (Credentials $passedCredentials) use (&$fallbackStepInvocationCount) {
            $fallbackStepInvocationCount++;

            return CheckResult::ABSTAIN;
        });

        $otherwise = new OtherwiseStep($mainStep, $fallbackStep);

        try {
            $otherwise->check(new Credentials(new StepData(), new Request()));
            $this->fail('should not be executed!');
        } catch (\Throwable $catched) {
            $this->assertSame($exception, $catched);
        }

        $this->assertEquals(1, $mainStepInvocationCount);
        $this->assertEquals(0, $fallbackStepInvocationCount);
    }

    public function testFallbackStepFail()
    {
        $mainStepInvocationCount = 0;
        $mainStep = new CallableStep(function (Credentials $passedCredentials) use (&$mainStepInvocationCount) {
            $mainStepInvocationCount++;

            return CheckResult::ABSTAIN;
        });

        $fallbackStepInvocationCount = 0;
        $exception = new \RuntimeException('bla-bla');
        $fallbackStep = new CallableStep(function (Credentials $passedCredentials) use (&$fallbackStepInvocationCount, $exception) {
            $fallbackStepInvocationCount++;

            throw $exception;
        });

        $otherwise = new OtherwiseStep($mainStep, $fallbackStep);

        try {
            $otherwise->check(new Credentials(new StepData(), new Request()));
            $this->fail('should not be executed!');
        } catch (\Throwable $catched) {
            $this->assertSame($exception, $catched);
        }

        $this->assertEquals(1, $mainStepInvocationCount);
        $this->assertEquals(1, $fallbackStepInvocationCount);
    }
}
