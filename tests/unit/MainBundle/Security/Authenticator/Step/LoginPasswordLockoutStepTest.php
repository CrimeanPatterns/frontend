<?php

namespace AwardWallet\Tests\Unit\MainBundle\Security\Authenticator\Step;

use AwardWallet\MainBundle\Entity\Usr;
use AwardWallet\MainBundle\Security\AntiBruteforceLockerService;
use AwardWallet\MainBundle\Security\Authenticator\Credentials;
use AwardWallet\MainBundle\Security\Authenticator\Step\CheckResult;
use AwardWallet\MainBundle\Security\Authenticator\Step\LoginPasswordLockoutStep;
use AwardWallet\MainBundle\Security\Authenticator\Step\StepData;
use AwardWallet\Tests\Unit\MainBundle\Security\Authenticator\Step\Fixtures\LockoutFixture;
use Codeception\TestCase\Test;
use Prophecy\Argument;
use Prophecy\PhpUnit\ProphecyTrait;
use Psr\Log\LoggerInterface;
use Symfony\Component\HttpFoundation\Request;

/**
 * @group frontend-unit
 * @group security
 * @covers \AwardWallet\MainBundle\Security\Authenticator\Step\LoginPasswordLockoutStep
 */
class LoginPasswordLockoutStepTest extends Test
{
    use ProphecyTrait;
    use ExpectStepExceptionTrait;

    public function testNoUserMeansNoLockoutCheck()
    {
        $loginLocker = $this->prophesize(AntiBruteforceLockerService::class);
        $passwordLocker = $this->prophesize(AntiBruteforceLockerService::class);
        $loginLocker
            ->checkForLockout(Argument::cetera())
            ->shouldNotBeCalled();
        $passwordLocker
            ->checkForLockout(Argument::cetera())
            ->shouldNotBeCalled();

        $step = new LoginPasswordLockoutStep(
            $loginLocker->reveal(),
            $passwordLocker->reveal(),
            $this->prophesize(LoggerInterface::class)->reveal()
        );

        $this->assertEquals(
            CheckResult::SUCCESS,
            $step->check(new Credentials(new StepData(), new Request()))
        );
    }

    public function testLoginLockout()
    {
        $user = new Usr();
        $user->setLogin($userLogin = 'somelogin');
        $loginLocker = $this->prophesize(AntiBruteforceLockerService::class);
        $loginLocker
            ->checkForLockout($userLogin)
            ->willReturn(LockoutFixture::ERROR_LOCKOUT_RESULT)
            ->shouldBeCalledOnce();

        $passwordLocker = $this->prophesize(AntiBruteforceLockerService::class);
        $passwordLocker
            ->checkForLockout(Argument::cetera())
            ->shouldNotBeCalled();

        $logger = $this->prophesize(LoggerInterface::class);
        $logger
            ->warning('Login antibruteforce check failed', Argument::any())
            ->shouldBeCalledOnce();

        $step = new LoginPasswordLockoutStep(
            $loginLocker->reveal(),
            $passwordLocker->reveal(),
            $logger->reveal()
        );

        $this->expectStepErrorException(LockoutFixture::ERROR_LOCKOUT_RESULT);

        $step->check((new Credentials(new StepData(), new Request()))->setUser($user));
    }

    public function testPasswordLockout()
    {
        $user = new Usr();
        $user->setLogin('somelogin');
        $loginLocker = $this->prophesize(AntiBruteforceLockerService::class);
        $loginLocker
            ->checkForLockout(Argument::any())
            ->willReturn(null)
            ->shouldBeCalledOnce();

        $passwordLocker = $this->prophesize(AntiBruteforceLockerService::class);
        $passwordLocker
            ->checkForLockout($userPassword = 'password')
            ->willReturn(LockoutFixture::ERROR_LOCKOUT_RESULT)
            ->shouldBeCalledOnce();

        $logger = $this->prophesize(LoggerInterface::class);
        $logger
            ->info('Login antibruteforce check passed', Argument::any())
            ->shouldBeCalledOnce();

        $logger
            ->warning('Password antibruteforce check failed', Argument::any())
            ->shouldBeCalledOnce();

        $step = new LoginPasswordLockoutStep(
            $loginLocker->reveal(),
            $passwordLocker->reveal(),
            $logger->reveal()
        );

        $this->expectStepErrorException('Bad credentials');

        $step->check(
            (new Credentials(
                (new StepData())->setPassword($userPassword),
                new Request()
            ))
            ->setUser($user)
        );
    }

    public function testNoLockout()
    {
        $user = new Usr();
        $user->setLogin('somelogin');
        $loginLocker = $this->prophesize(AntiBruteforceLockerService::class);
        $loginLocker
            ->checkForLockout(Argument::any())
            ->willReturn(null)
            ->shouldBeCalledOnce();

        $passwordLocker = $this->prophesize(AntiBruteforceLockerService::class);
        $passwordLocker
            ->checkForLockout(Argument::any())
            ->willReturn(null)
            ->shouldBeCalledOnce();

        $logger = $this->prophesize(LoggerInterface::class);
        $logger
            ->info('Login antibruteforce check passed', Argument::any())
            ->shouldBeCalledOnce();

        $logger
            ->info('Password antibruteforce check passed', Argument::any())
            ->shouldBeCalledOnce();

        $step = new LoginPasswordLockoutStep(
            $loginLocker->reveal(),
            $passwordLocker->reveal(),
            $logger->reveal()
        );

        $this->assertEquals(CheckResult::SUCCESS, $step->check(
            (new Credentials(
                (new StepData())->setPassword('somepass'),
                new Request()
            ))
            ->setUser($user)
        ));
    }
}
