<?php

namespace AwardWallet\Tests\Unit\MainBundle\Security\Authenticator\Step;

use AwardWallet\MainBundle\Entity\Usr;
use AwardWallet\MainBundle\Security\Authenticator\Credentials;
use AwardWallet\MainBundle\Security\Authenticator\Step\CheckResult;
use AwardWallet\MainBundle\Security\Authenticator\Step\LoadUserStep;
use AwardWallet\MainBundle\Security\Authenticator\Step\StepData;
use Codeception\TestCase\Test;
use Prophecy\Argument;
use Prophecy\PhpUnit\ProphecyTrait;
use Psr\Log\LoggerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Security\Core\Exception\UsernameNotFoundException;
use Symfony\Component\Security\Core\User\UserProviderInterface;

/**
 * @group frontend-unit
 * @group security
 * @covers \AwardWallet\MainBundle\Security\Authenticator\Step\LoadUserStep
 */
class LoadUserStepTest extends Test
{
    use ProphecyTrait;

    public function testEmptyLogin()
    {
        $userProvider = $this->prophesize(UserProviderInterface::class);
        $userProvider
            ->loadUserByUsername(Argument::cetera())
            ->shouldNotBeCalled();

        $logger = $this->prophesize(LoggerInterface::class);
        $logger
            ->warning('Empty login', Argument::cetera())
            ->shouldBeCalledOnce();

        $step = new LoadUserStep(
            $logger->reveal(),
            $userProvider->reveal()
        );

        $this->expectUserNotFoundException();

        $step->check(new Credentials(new StepData(), new Request()));
    }

    public function testUserLoaderThrowedAnExceptionAndItWasRefined()
    {
        $this->markTestSkipped('fake user loading');

        $userProvider = $this->prophesize(UserProviderInterface::class);
        $userProvider
            ->loadUserByUsername($login = 'login')
            ->willThrow(new UsernameNotFoundException('some error'))
            ->shouldBeCalledOnce();

        $logger = $this->prophesize(LoggerInterface::class);
        $logger
            ->warning('User was not found', Argument::cetera())
            ->shouldBeCalledOnce();

        $step = new LoadUserStep(
            $logger->reveal(),
            $userProvider->reveal()
        );

        $this->expectUserNotFoundException();

        $step->check(new Credentials(
            (new StepData())->setLogin($login),
            new Request()
        ));
    }

    public function testUserLoaderReturnedNothing()
    {
        $this->markTestSkipped('fake user loading');

        $userProvider = $this->prophesize(UserProviderInterface::class);
        $userProvider
            ->loadUserByUsername($login = 'login')
            ->willReturn(null)
            ->shouldBeCalledOnce();

        $logger = $this->prophesize(LoggerInterface::class);
        $logger
            ->warning('User was not found', Argument::cetera())
            ->shouldBeCalledOnce();

        $step = new LoadUserStep(
            $logger->reveal(),
            $userProvider->reveal()
        );

        $this->expectUserNotFoundException();

        $step->check(new Credentials(
            (new StepData())->setLogin($login),
            new Request()
        ));
    }

    public function testSuccessLoad()
    {
        $userProvider = $this->prophesize(UserProviderInterface::class);
        $userProvider
            ->loadUserByUsername($login = 'login')
            ->willReturn($user = new Usr())
            ->shouldBeCalledOnce();

        $logger = $this->prophesize(LoggerInterface::class);
        $logger
            ->info('User was loaded', Argument::cetera())
            ->shouldBeCalledOnce();

        $step = new LoadUserStep(
            $logger->reveal(),
            $userProvider->reveal()
        );

        $this->assertEquals(
            CheckResult::SUCCESS,
            $step->check($credentials = new Credentials(
                (new StepData())->setLogin($login),
                new Request()
            ))
        );
        $this->assertSame($user, $credentials->getUser());
    }

    protected function expectUserNotFoundException(): void
    {
        $this->expectException(UsernameNotFoundException::class);
        $this->expectExceptionMessage('Bad credentials');
    }
}
