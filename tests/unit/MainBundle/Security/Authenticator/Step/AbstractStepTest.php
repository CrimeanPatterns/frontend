<?php

namespace AwardWallet\Tests\Unit\MainBundle\Security\Authenticator\Step;

use AwardWallet\MainBundle\Security\Authenticator\Credentials;
use AwardWallet\MainBundle\Security\Authenticator\Step\AbstractStep;
use AwardWallet\MainBundle\Security\Authenticator\Step\CheckResult;
use AwardWallet\MainBundle\Security\Authenticator\Step\StepData;
use Codeception\TestCase\Test;
use Prophecy\PhpUnit\ProphecyTrait;
use Symfony\Component\HttpFoundation\Request;

/**
 * @group frontend-unit
 * @group security
 * @coversDefaultClass \AwardWallet\MainBundle\Security\Authenticator\Step\AbstractStep
 */
class AbstractStepTest extends Test
{
    use ProphecyTrait;

    public function testDefaultCheckImplementationShouldAbstainOnNonSupportedCredentials()
    {
        $step = new class() extends AbstractStep {
            protected function doCheck(Credentials $credentials): void
            {
                throw new \LogicException('should not be executed!');
            }

            protected function supports(Credentials $credentials): bool
            {
                return false;
            }
        };

        $this->assertEquals(
            CheckResult::ABSTAIN,
            $step->check(new Credentials(new StepData(), new Request()))
        );
    }

    public function testDefaultCheckImplementationShouldSucceedIfDoCheckSucceed()
    {
        $step = new class() extends AbstractStep {
            protected function doCheck(Credentials $credentials): void
            {
            }

            protected function supports(Credentials $credentials): bool
            {
                return true;
            }
        };

        $this->assertEquals(
            CheckResult::SUCCESS,
            $step->check(new Credentials(new StepData(), new Request()))
        );
    }

    public function testSupportByDefault()
    {
        $step = new class() extends AbstractStep {
            protected function doCheck(Credentials $credentials): void
            {
            }
        };

        $this->assertEquals(
            CheckResult::SUCCESS,
            $step->check(new Credentials(new StepData(), new Request()))
        );
    }
}
