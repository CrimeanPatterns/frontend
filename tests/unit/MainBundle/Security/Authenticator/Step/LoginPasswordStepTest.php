<?php

namespace AwardWallet\Tests\Unit\MainBundle\Security\Authenticator\Step;

use AwardWallet\MainBundle\Entity\Usr;
use AwardWallet\MainBundle\Security\Authenticator\Credentials;
use AwardWallet\MainBundle\Security\Authenticator\Step\CheckResult;
use AwardWallet\MainBundle\Security\Authenticator\Step\Exception\ErrorStepAuthenticationException;
use AwardWallet\MainBundle\Security\Authenticator\Step\LoginPasswordStep;
use AwardWallet\MainBundle\Security\Authenticator\Step\StepData;
use AwardWallet\MainBundle\Security\PasswordChecker;
use AwardWallet\Tests\Unit\BaseTest;
use Prophecy\Argument;
use Psr\Log\LoggerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Security\Core\Exception\BadCredentialsException;

/**
 * @group frontend-unit
 * @group security
 * @covers \AwardWallet\MainBundle\Security\Authenticator\Step\LoginPasswordStep
 */
class LoginPasswordStepTest extends BaseTest
{
    public function testValidPassword()
    {
        $loggerProphecy = $this->prophesize(LoggerInterface::class);
        $loggerProphecy
            ->info('Credentials check passed', Argument::cetera())
            ->shouldBeCalled();
        $user = new Usr();
        $password = 'password';

        $passwordChecker = $this->prophesize(PasswordChecker::class);
        $passwordChecker
            ->checkPasswordUnsafe($user, $password)
            ->shouldBeCalled();

        $step = new LoginPasswordStep(
            $loggerProphecy->reveal(),
            $passwordChecker->reveal()
        );
        $credentials =
            (new Credentials(
                (new StepData())
                ->setPassword($password),
                new Request()
            ))
            ->setUser($user);

        $this->assertEquals(
            CheckResult::SUCCESS,
            $step->check($credentials)
        );
    }

    public function testInvalidPasswordException()
    {
        $loggerProphecy = $this->prophesize(LoggerInterface::class);
        $loggerProphecy
            ->warning('Credentials check failed', Argument::cetera())
            ->shouldBeCalled();
        $user = new Usr();
        $password = 'password';

        $passwordChecker = $this->prophesize(PasswordChecker::class);
        $passwordChecker
            ->checkPasswordUnsafe($user, $password)
            ->willThrow(new BadCredentialsException('Bad credentials'));

        $step = new LoginPasswordStep(
            $loggerProphecy->reveal(),
            $passwordChecker->reveal()
        );
        $credentials =
            (new Credentials(
                (new StepData())
                ->setPassword($password),
                new Request()
            ))
            ->setUser($user);

        $this->expectException(ErrorStepAuthenticationException::class);
        $this->expectExceptionMessage('Bad credentials');
        $step->check($credentials);
    }
}
