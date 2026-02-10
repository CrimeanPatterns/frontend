<?php

namespace AwardWallet\Tests\Unit\MainBundle\Security\Authenticator\Step\OneTimeCodeByEmail;

use AwardWallet\MainBundle\Entity\OneTimeCode;
use AwardWallet\MainBundle\Entity\Usr;
use AwardWallet\MainBundle\Event\OneTimeCodeEvent;
use AwardWallet\MainBundle\FrameworkExtension\Mailer\Mailer;
use AwardWallet\MainBundle\FrameworkExtension\Mailer\Template\User\Otc;
use AwardWallet\MainBundle\Globals\Geo;
use AwardWallet\MainBundle\Security\Authenticator\Credentials;
use AwardWallet\MainBundle\Security\Authenticator\Step\CheckResult;
use AwardWallet\MainBundle\Security\Authenticator\Step\OneTimeCodeByEmail\OneTimeCodeByEmailStep;
use AwardWallet\MainBundle\Security\Authenticator\Step\OneTimeCodeByEmail\StorageOperations;
use AwardWallet\MainBundle\Security\Authenticator\Step\OneTimeCodeByEmail\SupportChecker;
use AwardWallet\MainBundle\Security\Authenticator\Step\StepData;
use AwardWallet\Tests\Unit\BaseTest;
use AwardWallet\Tests\Unit\MainBundle\Security\Authenticator\Step\ExpectStepExceptionTrait;
use AwardWallet\Tests\Unit\MainBundle\Security\Authenticator\Step\MakeTranslatorMockTrait;
use Prophecy\Argument;
use Psr\Log\LoggerInterface;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\HttpFoundation\Request;

/**
 * @group frontend-unit
 * @group security
 * @coversDefaultClass \AwardWallet\MainBundle\Security\Authenticator\Step\OneTimeCodeByEmail\OneTimeCodeByEmailStep
 */
class OneTimeCodeByEmailStepTest extends BaseTest
{
    use MakeTranslatorMockTrait;
    use ExpectStepExceptionTrait;

    public function testCheckIsNotSupported()
    {
        $credentials = (new Credentials(new StepData(), new Request()));

        $step = $this->makeProphesizedMuted(OneTimeCodeByEmailStep::class, [
            SupportChecker::class => $this->makeSupportCheckerMock($credentials, false),
        ]);

        $this->assertEquals(CheckResult::ABSTAIN, $step->check($credentials));
    }

    public function testSuccessCheck()
    {
        $user = new Usr();
        $user->setEmail('some@email.com');
        $credentials = (new Credentials(
            (new StepData())
                ->setOtcEmailCode($presentedCode = '123455'),
            new Request()
        ))->setUser($user);

        $logger = $this->prophesize(LoggerInterface::class);
        $logger
            ->info('Provided email-OTC is valid', Argument::type('array'))
            ->shouldBeCalledOnce();

        $storageOperations = $this->prophesize(StorageOperations::class);
        $storageOperations
            ->findUserCodes($user)
            ->willReturn([(new OneTimeCode())->setCreationDate(new \DateTime('-1 minute'))])
            ->shouldBeCalledOnce();
        $storageOperations
            ->findUserCode($user, $presentedCode)
            ->willReturn(new OneTimeCode())
            ->shouldBeCalledOnce();
        $storageOperations
            ->deleteUserCodes($user)
            ->shouldBeCalled();

        $step = $this->makeProphesizedMuted(OneTimeCodeByEmailStep::class, [
            SupportChecker::class => $this->makeSupportCheckerMock($credentials, true),
            StorageOperations::class => $storageOperations->reveal(),
            LoggerInterface::class => $logger->reveal(),
        ]);

        $this->assertEquals(CheckResult::SUCCESS, $step->check($credentials));
    }

    public function getCodeInDbIsOldOrAbsentDataProvider()
    {
        return [
            'code is old' => [function () {
                return [(new OneTimeCode())->setCreationDate(
                    new \DateTime('-20 minutes')
                )];
            }],
            'code is absent' => [function () { return []; }],
        ];
    }

    /**
     * @dataProvider getCodeInDbIsOldOrAbsentDataProvider
     */
    public function testCodeInDbIsOldOrAbsent(callable $codeInDbProvider)
    {
        $user = new Usr();
        $user->setEmail('some@email.com');
        $credentials = (new Credentials(
            new StepData(),
            new Request()
        ))->setUser($user);

        $logger = $this->prophesize(LoggerInterface::class);
        $logger
            ->warning('Email-OTC is not provided', Argument::type('array'))
            ->shouldBeCalledOnce();

        $storageOperations = $this->prophesize(StorageOperations::class);
        $storageOperations
            ->findUserCodes($user)
            ->willReturn($codeInDbProvider())
            ->shouldBeCalledOnce();
        $storageOperations
            ->saveCode(Argument::any())
            ->shouldBeCalled();
        $storageOperations
            ->saveLastCodes(Argument::type('array'), 10)
            ->shouldBeCalled();

        $geo = $this->prophesize(Geo::class);
        $geo
            ->getLocationByIp(Argument::cetera())
            ->willReturn($geoIp = ['ip' => $ip = '10.10.10.10'])
            ->shouldBeCalled();
        $geo
            ->getLocationName($geoIp)
            ->shouldBeCalled();

        $mailer = $this->prophesize(Mailer::class);
        $mailer
            ->getMessageByTemplate(Argument::that(function ($template) use ($ip) {
                $this->assertInstanceOf(Otc::class, $template);
                $this->assertEquals($ip, $template->lastIp);

                return true;
            }))
            ->shouldBeCalled();
        $mailer
            ->send(Argument::cetera())
            ->shouldBeCalled();

        $eventDispatcher = $this->prophesize(EventDispatcherInterface::class);
        $eventDispatcher
            ->dispatch(
                OneTimeCodeEvent::NAME,
                Argument::that(function (/** @var $event OneTimeCodeEvent */ $event) use ($user) {
                    $this->assertInstanceOf(OneTimeCodeEvent::class);
                    $this->assertSame($user, $event->getUser());

                    return true;
                })
            );

        $step = new OneTimeCodeByEmailStep(
            $logger->reveal(),
            $geo->reveal(),
            $storageOperations->reveal(),
            $mailer->reveal(),
            $eventDispatcher->reveal(),
            $this->makeTranslatorMock([
                'error.auth.two-factor.email-code-required' => $error = "We've noticed bla-bla",
            ]),
            $this->makeSupportCheckerMock($credentials, true)
        );

        $this->expectStepRequiredException($error);

        $step->check($credentials);
    }

    public function testInvalidOtcProvided()
    {
        $user = new Usr();
        $credentials = (new Credentials(
            (new StepData())->setOtcEmailCode($providedOtc = '123456'),
            new Request()
        ))->setUser($user);

        $logger = $this->prophesize(LoggerInterface::class);
        $logger
            ->warning('Provided email-OTC is invalid', Argument::type('array'))
            ->shouldBeCalledOnce();

        $storageOperations = $this->prophesize(StorageOperations::class);
        $storageOperations
            ->findUserCodes($user)
            ->willReturn([
                (new OneTimeCode())
                ->setCreationDate(new \DateTime('-1 minute')),
            ])
            ->shouldBeCalledOnce();
        $storageOperations
            ->findUserCode($user, $providedOtc)
            ->willReturn(null)
            ->shouldBeCalledOnce();

        $geo = $this->prophesize(Geo::class);
        $mailer = $this->prophesize(Mailer::class);
        $eventDispatcher = $this->prophesize(EventDispatcherInterface::class);

        $step = new OneTimeCodeByEmailStep(
            $logger->reveal(),
            $geo->reveal(),
            $storageOperations->reveal(),
            $mailer->reveal(),
            $eventDispatcher->reveal(),
            $this->makeTranslatorMock([
                'error.auth.email.invalid-code' => $error = "The access code you've entered is not valid",
            ]),
            $this->makeSupportCheckerMock($credentials, true)
        );

        $this->expectStepErrorException($error);

        $step->check($credentials);
    }

    protected function makeSupportCheckerMock(Credentials $credentials, bool $supports): SupportChecker
    {
        $supportChecker = $this->prophesize(SupportChecker::class);
        $supportChecker
            ->supports($credentials, Argument::type('array'))
            ->willReturn($supports)
            ->shouldBeCalledOnce();

        return $supportChecker->reveal();
    }
}
