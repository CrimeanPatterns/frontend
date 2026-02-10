<?php

namespace AwardWallet\Tests\Unit\MainBundle\Security\Authenticator\Step;

use AwardWallet\MainBundle\Entity\Repositories\UsrRepository;
use AwardWallet\MainBundle\Entity\Usr;
use AwardWallet\MainBundle\Security\Authenticator\Credentials;
use AwardWallet\MainBundle\Security\Authenticator\Step\BusinessAdminStep;
use AwardWallet\MainBundle\Security\Authenticator\Step\CheckResult;
use AwardWallet\MainBundle\Security\Authenticator\Step\StepData;
use Codeception\TestCase\Test;
use Prophecy\Argument;
use Prophecy\PhpUnit\ProphecyTrait;
use Psr\Log\LoggerInterface;
use Symfony\Component\HttpFoundation\Request;

/**
 * @group frontend-unit
 * @group security
 * @coversDefaultClass \AwardWallet\MainBundle\Security\Authenticator\Step\BusinessAdminStep
 */
class BusinessAdminStepTest extends Test
{
    use ProphecyTrait;
    use ExpectStepExceptionTrait;

    public function testAbstaningOnNonBusinessHost()
    {
        $businessHost = 'somebusiness.com';
        $request = $this->prophesize(Request::class);
        $request
            ->getHost()
            ->willReturn('nonbusiness.com');
        $request
            ->getClientIp()
            ->willReturn('127.0.0.2');

        $logger = $this->prophesize(LoggerInterface::class);
        $logger
            ->info('No business host is detected', Argument::type('array'))
            ->shouldBeCalledOnce();

        $step = new BusinessAdminStep(
            $businessHost,
            $this->prophesize(UsrRepository::class)->reveal(),
            $logger->reveal()
        );

        $credentials = new Credentials(
            new StepData(),
            $request->reveal()
        );
        $this->assertEquals(CheckResult::ABSTAIN, $step->check($credentials));
    }

    public function testBusinessUserNotFoundFail()
    {
        $businessHost = 'somebusiness.com';
        $request = $this->prophesize(Request::class);
        $request
            ->getHost()
            ->willReturn($businessHost);
        $request
            ->getClientIp()
            ->willReturn('127.0.0.2');

        $logger = $this->prophesize(LoggerInterface::class);
        $logger
            ->info('Business host is detected', Argument::type('array'))
            ->shouldBeCalledOnce();

        $user = new Usr();
        $userRepository = $this->prophesize(UsrRepository::class);
        $userRepository
            ->getBusinessByUser(Argument::exact($user))
            ->will(function () use ($logger) {
                $logger
                    ->warning('No business accounts were found for user', Argument::type('array'))
                    ->shouldBeCalledOnce();

                return null;
            })
            ->shouldBeCalledOnce();

        $step = new BusinessAdminStep(
            $businessHost,
            $userRepository->reveal(),
            $logger->reveal()
        );

        $credentials =
            (new Credentials(new StepData(), $request->reveal()))
            ->setUser($user);

        $this->expectStepErrorException("You are not an administrator of any business account");

        $step->check($credentials);
    }

    public function testBusinessUserNotFoundOnBusinessHostFail()
    {
        $businessHost = 'somebusiness.com';
        $request = $this->prophesize(Request::class);
        $request
            ->getHost()
            ->willReturn($businessHost);
        $request
            ->getClientIp()
            ->willReturn('127.0.0.2');

        $logger = $this->prophesize(LoggerInterface::class);
        $logger
            ->info('Business host is detected', Argument::type('array'))
            ->shouldBeCalledOnce();

        $user = new Usr();
        $userRepository = $this->prophesize(UsrRepository::class);
        $userRepository
            ->getBusinessByUser(Argument::exact($user))
            ->will(function () use ($logger) {
                $logger
                    ->warning('No business accounts were found for user', Argument::type('array'))
                    ->shouldBeCalledOnce();

                return null;
            })
            ->shouldBeCalledOnce();

        $step = new BusinessAdminStep(
            $businessHost,
            $userRepository->reveal(),
            $logger->reveal()
        );

        $credentials =
            (new Credentials(new StepData(), $request->reveal()))
            ->setUser($user);

        $this->expectStepErrorException("You are not an administrator of any business account");

        $step->check($credentials);
    }

    public function testBusinessUserFoundFail()
    {
        $businessHost = 'somebusiness.com';
        $request = $this->prophesize(Request::class);
        $request
            ->getHost()
            ->willReturn($businessHost);
        $request
            ->getClientIp()
            ->willReturn('127.0.0.2');

        $logger = $this->prophesize(LoggerInterface::class);
        $logger
            ->info('Business host is detected', Argument::type('array'))
            ->shouldBeCalledOnce();

        $user = new Usr();
        $userRepository = $this->prophesize(UsrRepository::class);
        $userRepository
            ->getBusinessByUser(Argument::exact($user))
            ->will(function () use ($logger) {
                $logger
                    ->info('Business account was found', Argument::type('array'))
                    ->shouldBeCalledOnce();

                return new Usr();
            })
            ->shouldBeCalledOnce();

        $step = new BusinessAdminStep(
            $businessHost,
            $userRepository->reveal(),
            $logger->reveal()
        );

        $credentials =
            (new Credentials(new StepData(), $request->reveal()))
            ->setUser($user);

        $this->assertEquals(CheckResult::SUCCESS, $step->check($credentials));
    }
}
