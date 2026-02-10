<?php

namespace AwardWallet\Tests\Unit\MainBundle\Security\Authenticator\Step;

use AwardWallet\MainBundle\Security\AntiBruteforceLockerService;
use AwardWallet\MainBundle\Security\Authenticator\Credentials;
use AwardWallet\MainBundle\Security\Authenticator\Step\CheckResult;
use AwardWallet\MainBundle\Security\Authenticator\Step\IpLockoutStep;
use AwardWallet\MainBundle\Security\Authenticator\Step\StepData;
use AwardWallet\Tests\Unit\MainBundle\Security\Authenticator\Step\Fixtures\LockoutFixture;
use Codeception\TestCase\Test;
use Prophecy\Argument;
use Prophecy\PhpUnit\ProphecyTrait;
use Psr\Log\LoggerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Security\Core\Exception\AuthenticationException;

/**
 * @covers \AwardWallet\MainBundle\Security\Authenticator\Step\IpLockoutStep
 * @group frontend-unit
 * @group security
 */
class IpLockoutStepTest extends Test
{
    use ProphecyTrait;
    use ExpectStepExceptionTrait;

    public function testLockout()
    {
        $request = $this->prophesize(Request::class);
        $request
            ->getClientIp()
            ->willReturn($ip = '10.10.10.10');

        $ipLocker = $this->prophesize(AntiBruteforceLockerService::class);
        $ipLocker
            ->checkForLockout($ip, true)
            ->willReturn(LockoutFixture::ERROR_LOCKOUT_RESULT)
            ->shouldBeCalledOnce();

        $logger = $this->prophesize(LoggerInterface::class);
        $logger
            ->warning('IP antibruteforce check failed', Argument::any())
            ->shouldBeCalledOnce();

        $step = new IpLockoutStep(
            $ipLocker->reveal(),
            $logger->reveal()
        );

        $this->expectStepErrorException(LockoutFixture::ERROR_LOCKOUT_RESULT);

        $step->check(
            new Credentials(
                new StepData(),
                $request->reveal()
            )
        );
    }

    public function testNoLockout()
    {
        $request = $this->prophesize(Request::class);
        $request
            ->getClientIp()
            ->willReturn($ip = '10.10.10.10');

        $ipLocker = $this->prophesize(AntiBruteforceLockerService::class);
        $ipLocker
            ->checkForLockout($ip, true)
            ->willReturn(null)
            ->shouldBeCalledOnce();

        $logger = $this->prophesize(LoggerInterface::class);
        $logger
            ->info('IP antibruteforce check passed', Argument::any())
            ->shouldBeCalledOnce();

        $step = new IpLockoutStep(
            $ipLocker->reveal(),
            $logger->reveal()
        );

        $this->assertEquals(
            CheckResult::SUCCESS,
            $step->check(
                new Credentials(
                    new StepData(),
                    $request->reveal()
                )
            )
        );
    }

    public function testAdditionalCheckOnFail()
    {
        $request = $this->prophesize(Request::class);
        $request
            ->getClientIp()
            ->willReturn($ip = '10.10.10.10');

        $ipLocker = $this->prophesize(AntiBruteforceLockerService::class);
        $ipLocker
            ->checkForLockout($ip)
            ->shouldBeCalledOnce();

        $step = new IpLockoutStep(
            $ipLocker->reveal(),
            $this->prophesize(LoggerInterface::class)->reveal()
        );

        $step->onFail(
            $request->reveal(),
            $this->prophesize(AuthenticationException::class)->reveal()
        );
    }
}
