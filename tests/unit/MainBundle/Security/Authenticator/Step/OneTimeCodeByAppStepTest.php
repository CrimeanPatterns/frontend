<?php

namespace AwardWallet\Tests\Unit\MainBundle\Security\Authenticator\Step;

use AwardWallet\Common\PasswordCrypt\PasswordDecryptor;
use AwardWallet\MainBundle\Entity\Usr;
use AwardWallet\MainBundle\Security\Authenticator\Credentials;
use AwardWallet\MainBundle\Security\Authenticator\Step\CheckResult;
use AwardWallet\MainBundle\Security\Authenticator\Step\OneTimeCodeByAppStep;
use AwardWallet\MainBundle\Security\Authenticator\Step\StepData;
use AwardWallet\MainBundle\Security\TwoFactorAuthentication\TwoFactorAuthenticationService;
use AwardWallet\Tests\Unit\BaseTest;
use Prophecy\Argument;
use Prophecy\Prophecy\ObjectProphecy;
use Psr\Log\LoggerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Contracts\Translation\TranslatorInterface;

/**
 * @coversDefaultClass \AwardWallet\MainBundle\Security\Authenticator\Step\OneTimeCodeByAppStep
 * @group frontend-unit
 * @group security
 */
class OneTimeCodeByAppStepTest extends BaseTest
{
    use MakeTranslatorMockTrait;
    use MakeLoggerMockTrait;
    use ExpectStepExceptionTrait;

    public function testDisabled2FactorApp()
    {
        $logger = $this->prophesize(LoggerInterface::class);
        $logger
            ->info('2factor protection by app is disabled', Argument::type('array'))
            ->shouldBeCalled();

        $step = $this->makeProphesizedMuted(OneTimeCodeByAppStep::class, [
            LoggerInterface::class => $logger->reveal(),
        ]);
        $this->assertEquals(CheckResult::ABSTAIN, $step->check(
            (new Credentials(new StepData(), new Request()))
            ->setUser(new Usr())
        ));
    }

    public function testEnabled2FactorAndRequired()
    {
        $logger = $this->makeLogger2FactorEnabledProphecy();
        $logger
            ->warning('App-OTC is empty', Argument::type('array'))
            ->shouldBeCalled();

        $passwordDecryptor = $this->prophesize(PasswordDecryptor::class);
        $passwordDecryptor
            ->decrypt("some")
            ->willReturn("some")
            ->shouldBeCalledOnce();

        $step = $this->makeProphesizedMuted(OneTimeCodeByAppStep::class, [
            LoggerInterface::class => $logger->reveal(),
            TranslatorInterface::class => $this->makeTranslatorMock([
                'error.auth.two-factor.code-required' => $error = 'Code Required',
            ]),
            PasswordDecryptor::class => $passwordDecryptor->reveal(),
        ]);

        $this->expectStepRequiredException($error);

        $step->check(
            (new Credentials(
                new StepData(),
                new Request()
            ))
            ->setUser($this->makeUserWith2Factor())
        );
    }

    public function testPossibleReplayAttack()
    {
        $logger = $this->makeLogger2FactorEnabledProphecy();
        $logger
            ->warning(
                Argument::containingString('Possible OTC replay attack on User'),
                Argument::type('array')
            )
            ->shouldBeCalled();

        $credentials =
            (new Credentials(
                (new StepData())->setOtcAppCode($providedCode = '123456'),
                new Request()
            ))
            ->setUser($this->makeUserWith2Factor());

        $cache = $this->prophesize(\Memcached::class);
        $cache
            ->get(Argument::cetera())
            ->willReturn((int) $providedCode)
            ->shouldBeCalledOnce();

        $passwordDecryptor = $this->prophesize(PasswordDecryptor::class);
        $passwordDecryptor
            ->decrypt("some")
            ->willReturn("some")
            ->shouldBeCalledOnce();

        $step = new OneTimeCodeByAppStep(
            $this->prophesize(TwoFactorAuthenticationService::class)->reveal(),
            $logger->reveal(),
            $this->makeTranslatorMock([
                'error.auth.two-factor.invalid-code' => $error = 'Code Invalid',
            ]),
            $cache->reveal(),
            $passwordDecryptor->reveal()
        );

        $this->expectStepErrorException($error);

        $step->check($credentials);
    }

    public function testSuccessCheck()
    {
        $credentials =
            (new Credentials(
                (new StepData())->setOtcAppCode($providedCode = '123456'),
                new Request()
            ))
            ->setUser(
                $this->makeUserWith2Factor()
                ->setGoogleAuthSecret($authSecret = 'authSecret')
            );

        $twoFactorAuth = $this->prophesize(TwoFactorAuthenticationService::class);
        $twoFactorAuth
            ->checkCode($authSecret, $providedCode)
            ->willReturn(true)
            ->shouldBeCalledOnce();

        $passwordDecryptor = $this->prophesize(PasswordDecryptor::class);
        $passwordDecryptor
            ->decrypt($authSecret)
            ->willReturn($authSecret)
            ->shouldBeCalledOnce();

        $step = new OneTimeCodeByAppStep(
            $twoFactorAuth->reveal(),
            $this->makeLogger2FactorEnabledMock([
                ['info', ["otc validation", Argument::type('array')]],
                ['info', ["App-OTC is valid", Argument::type('array')]],
            ]),
            $this->makeTranslatorMock(),
            $this->prophesize(\Memcached::class)->reveal(),
            $passwordDecryptor->reveal()
        );

        $this->assertEquals(CheckResult::SUCCESS, $step->check($credentials));
    }

    public function testInvalidCode()
    {
        $credentials =
            (new Credentials(
                (new StepData())->setOtcAppCode($providedCode = '123456'),
                new Request()
            ))
            ->setUser(
                $this->makeUserWith2Factor()
                ->setGoogleAuthSecret($authSecret = 'authSecret')
            );

        $twoFactorAuth = $this->prophesize(TwoFactorAuthenticationService::class);
        $twoFactorAuth
            ->checkCode($authSecret, $providedCode)
            ->willReturn(false)
            ->shouldBeCalledOnce();

        $passwordDecryptor = $this->prophesize(PasswordDecryptor::class);
        $passwordDecryptor
            ->decrypt("authSecret")
            ->willReturn("authSecret")
            ->shouldBeCalledOnce();

        $step = new OneTimeCodeByAppStep(
            $twoFactorAuth->reveal(),
            $this->makeLogger2FactorEnabledMock([
                ['info', ["otc validation", Argument::type('array')]],
                ['warning', ['App-OTC is invalid', Argument::type('array')]],
            ]),
            $this->makeTranslatorMock([
                'error.auth.two-factor.invalid-code' => $error = 'Code Invalid',
            ]),
            $this->prophesize(\Memcached::class)->reveal(),
            $passwordDecryptor->reveal(),
        );

        $this->expectStepErrorException($error);

        $step->check($credentials);
    }

    /**
     * @return ObjectProphecy|LoggerInterface
     */
    protected function makeLogger2FactorEnabledProphecy(array $calls = [])
    {
        return $this->makeLoggerProphecy(\array_merge(
            [['info', ['2factor protection by app is enabled', Argument::type('array')]]],
            $calls
        ));
    }

    protected function makeLogger2FactorEnabledMock(array $calls = []): LoggerInterface
    {
        return $this->makeLogger2FactorEnabledProphecy($calls)->reveal();
    }

    protected function makeUserWith2Factor(): Usr
    {
        return (new Usr())
            ->setPass('xxx')
            ->setGoogleAuthRecoveryCode('some')
            ->setGoogleAuthSecret('some');
    }
}
