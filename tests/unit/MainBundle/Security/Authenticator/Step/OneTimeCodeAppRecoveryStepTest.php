<?php

namespace AwardWallet\Tests\Unit\MainBundle\Security\Authenticator\Step;

use AwardWallet\Common\PasswordCrypt\PasswordDecryptor;
use AwardWallet\MainBundle\Entity\Usr;
use AwardWallet\MainBundle\Security\Authenticator\Credentials;
use AwardWallet\MainBundle\Security\Authenticator\Step\CheckResult;
use AwardWallet\MainBundle\Security\Authenticator\Step\OneTimeCodeByAppRecoveryStep;
use AwardWallet\MainBundle\Security\Authenticator\Step\StepData;
use AwardWallet\MainBundle\Security\TwoFactorAuthentication\TwoFactorAuthenticationService;
use AwardWallet\Tests\Unit\BaseTest;
use Prophecy\Argument;
use Prophecy\Prophecy\ObjectProphecy;
use Psr\Log\LoggerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Contracts\Translation\TranslatorInterface;

/**
 * @coversDefaultClass \AwardWallet\MainBundle\Security\Authenticator\Step\OneTimeCodeByAppRecoveryStep
 * @group frontend-unit
 * @group security
 */
class OneTimeCodeAppRecoveryStepTest extends BaseTest
{
    use MakeTranslatorMockTrait;
    use MakeLoggerMockTrait;
    use ExpectStepExceptionTrait;

    public function getdisabled2FactorAppDataProvider()
    {
        return [
            'code is empty' => [function (StepData $stepData) { return $stepData; }],
            'code is present' => [function (StepData $stepData) { return $stepData->setOtcRecoveryCode('someblablal'); }],
        ];
    }

    /**
     * @dataProvider getdisabled2FactorAppDataProvider
     */
    public function testCheckIsNotTriggered(callable $stepDataProvider)
    {
        $logger = $this->prophesize(LoggerInterface::class);
        $logger
            ->info('2factor protection by app is disabled or recovery code is not provided or empty', Argument::type('array'))
            ->shouldBeCalled();

        $step = $this->makeProphesizedMuted(OneTimeCodeByAppRecoveryStep::class, [
            LoggerInterface::class => $logger->reveal(),
        ]);

        $this->assertEquals(CheckResult::ABSTAIN, $step->check(
            (new Credentials($stepDataProvider(new StepData()), new Request()))
            ->setUser(new Usr())
        ));
    }

    public function testSuccessCheck()
    {
        $credentials =
            (new Credentials(
                (new StepData())
                    ->setOtcRecoveryCode($otcRecovery = 'otcrecovery'),
                new Request()
            ))
            ->setUser($user =
                $this->makeUserWith2Factor()
                ->setGoogleAuthRecoveryCode($otcRecovery)
            );

        $twoFactorChecker = $this->prophesize(TwoFactorAuthenticationService::class);
        $twoFactorChecker
            ->cancelTwoFactor($user)
            ->shouldBeCalled();

        $passwordDecryptor = $this->prophesize(PasswordDecryptor::class);
        $passwordDecryptor
            ->decrypt("otcrecovery")
            ->willReturn("otcrecovery")
            ->shouldBeCalledOnce();

        $step = $this->makeProphesizedMuted(OneTimeCodeByAppRecoveryStep::class, [
            TwoFactorAuthenticationService::class => $twoFactorChecker->reveal(),
            LoggerInterface::class => $this->makeLogger2FactorEnabledMock([
                ['info', ["Recovery code is valid, disabling otc by recovery code", Argument::type('array')]],
            ]),
            PasswordDecryptor::class => $passwordDecryptor->reveal(),
        ]);

        $this->assertEquals(CheckResult::SUCCESS, $step->check($credentials));
    }

    public function testInvalidCodeProvided()
    {
        $credentials =
            (new Credentials(
                (new StepData())
                    ->setOtcRecoveryCode($otcRecovery = 'otcrecovery'),
                new Request()
            ))
            ->setUser($user =
                $this->makeUserWith2Factor()
                ->setGoogleAuthRecoveryCode('someothercode')
            );

        $passwordDecryptor = $this->prophesize(PasswordDecryptor::class);
        $passwordDecryptor
            ->decrypt("someothercode")
            ->willReturn("someothercode")
            ->shouldBeCalledOnce();

        $step = $this->makeProphesizedMuted(OneTimeCodeByAppRecoveryStep::class, [
            TranslatorInterface::class => $this->makeTranslatorMock([
                'error.auth.two-factor.invalid-recovery-code' => $error = 'Code Invalid',
            ]),
            LoggerInterface::class => $this->makeLogger2FactorEnabledMock([
                ['warning', ['Recovery code is invalid', Argument::type('array')]],
            ]),
            PasswordDecryptor::class => $passwordDecryptor->reveal(),
        ]);

        $this->expectStepErrorException($error);

        $step->check($credentials);
    }

    /**
     * @return ObjectProphecy|LoggerInterface
     */
    protected function makeLogger2FactorEnabledProphecy(array $calls = [])
    {
        return $this->makeLoggerProphecy(\array_merge(
            [['info', ['2factor protection by app is enabled and recovery code is provided', Argument::type('array')]]],
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
