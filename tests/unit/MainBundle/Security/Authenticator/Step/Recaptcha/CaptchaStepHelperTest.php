<?php

namespace AwardWallet\Tests\Unit\MainBundle\Security\Authenticator\Step\Recaptcha;

use AwardWallet\MainBundle\Security\Authenticator\Credentials;
use AwardWallet\MainBundle\Security\Authenticator\Step\Recaptcha\CaptchaStepHelper;
use AwardWallet\MainBundle\Security\Authenticator\Step\StepData;
use AwardWallet\MainBundle\Security\Captcha\Provider\CaptchaProviderInterface;
use AwardWallet\MainBundle\Security\Captcha\Validator\RecaptchaValidator;
use AwardWallet\MainBundle\Security\SiegeModeDetector;
use AwardWallet\Tests\Modules\Utils\Prophecy\ArgumentExtended;
use AwardWallet\Tests\Unit\BaseTest;
use AwardWallet\Tests\Unit\MainBundle\Security\Authenticator\Step\ExpectStepExceptionTrait;
use AwardWallet\Tests\Unit\MainBundle\Security\Authenticator\Step\Fixtures\UnconditionallySupportingRecaptchaStep;
use AwardWallet\Tests\Unit\MainBundle\Security\Authenticator\Step\MakeLoggerMockTrait;
use Clock\ClockInterface;
use Clock\ClockTest;
use Prophecy\Argument;
use Psr\Log\LoggerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Session\Session;

/**
 * @group frontend-unit
 * @group security
 * @coversDefaultClass \AwardWallet\MainBundle\Security\Authenticator\Step\Recaptcha\CaptchaStepHelper
 */
class CaptchaStepHelperTest extends BaseTest
{
    use ExpectStepExceptionTrait;
    use MakeLoggerMockTrait;

    protected const DEFAULT_SITE_KEY = 'somekey';

    public function testSuccessCheck()
    {
        $request = new Request([], [], [], [], [], [
            'REMOTE_ADDR' => $ip = '10.10.10.10',
        ]);
        $credentials = new Credentials(
            (new StepData())
                ->setRecaptcha($recaptchaProvided = 'abcd')
                ->setLogin($loginProvided = 'somelogin'),
            $request
        );
        $session = $this->prophesize(Session::class);
        $session
            ->has(UnconditionallySupportingRecaptchaStep::ID)
            ->willReturn(false)
            ->shouldBeCalledOnce();
        $session
            ->set(UnconditionallySupportingRecaptchaStep::ID, ArgumentExtended::containsArray([
                'client_recaptcha_secret' => $recaptchaProvided,
                'client_recaptcha_ip' => $ip,
                'client_recaptcha_login' => $loginProvided,
            ]))
            ->shouldBeCalledOnce();

        $request->setSession($session->reveal());

        $captchaProvider = $this->prophesize(CaptchaProviderInterface::class)
            ->getValidator()
            ->willReturn(
                $this->prophesize(RecaptchaValidator::class)
                    ->validate($recaptchaProvided, $ip, false)
                    ->willReturn(true)
                    ->shouldBeCalledOnce()
                    ->getObjectProphecy()
            )
            ->getObjectProphecy()

            ->getSiteKey()
            ->willReturn(self::DEFAULT_SITE_KEY)
            ->getObjectProphecy()

            ->getVendor()
            ->willReturn('stub')
            ->getObjectProphecy()

            ->getScriptUrl(Argument::cetera())
            ->willReturn('someurl')
            ->getObjectProphecy();

        $logger = $this->prophesize(LoggerInterface::class);
        $logger
            ->info("Captcha online check succeeded", Argument::cetera())
            ->shouldBeCalledOnce();

        $stepHelper = $this->makeStepWithEnabledRecaptcha([
            LoggerInterface::class => $logger->reveal(),
            ClockInterface::class => new ClockTest(),
        ]);
        $step = new UnconditionallySupportingRecaptchaStep();

        $this->assertTrue($stepHelper->supports($step, $credentials));
        $stepHelper->doCheck(
            $step,
            $credentials,
            $captchaProvider->reveal()
        );
    }

    public function testInvalidCheck()
    {
        $request = new Request([], [], [], [], [], [
            'REMOTE_ADDR' => $ip = '10.10.10.10',
        ]);
        $credentials = new Credentials(
            (new StepData())
                ->setRecaptcha($recaptchaProvided = 'abcd')
                ->setLogin($loginProvided = 'somelogin'),
            $request
        );
        $session = $this->prophesize(Session::class);
        $session
            ->has(UnconditionallySupportingRecaptchaStep::ID)
            ->willReturn(false)
            ->shouldBeCalledOnce();

        $request->setSession($session->reveal());

        $captchaProvider = $this->prophesize(CaptchaProviderInterface::class)
            ->getValidator()
            ->willReturn(
                $this->prophesize(RecaptchaValidator::class)
                    ->validate($recaptchaProvided, $ip, false)
                    ->willReturn(false)
                    ->shouldBeCalledOnce()
                    ->getObjectProphecy()
            )
            ->getObjectProphecy()

            ->getSiteKey()
            ->willReturn(self::DEFAULT_SITE_KEY)
            ->getObjectProphecy()

            ->getVendor()
            ->willReturn('stub')
            ->getObjectProphecy()

            ->getScriptUrl(Argument::cetera())
            ->willReturn('someurl')
            ->getObjectProphecy();

        $logger = $this->prophesize(LoggerInterface::class);
        $logger
            ->warning("Captcha online check failed", Argument::cetera())
            ->shouldBeCalledOnce();

        $stepHelper = $this->makeStepWithEnabledRecaptcha([
            LoggerInterface::class => $logger->reveal(),
            ClockInterface::class => new ClockTest(),
        ]);
        $step = new UnconditionallySupportingRecaptchaStep();

        $this->assertTrue($stepHelper->supports($step, $credentials));
        $this->expectStepErrorException('invalid_captcha');
        $stepHelper->doCheck(
            $step,
            $credentials,
            $captchaProvider->reveal()
        );
    }

    public function testDisabledRecaptchaCheck()
    {
        $credentials = new Credentials(
            new StepData(),
            new Request()
        );
        $logger = $this->prophesize(LoggerInterface::class);
        $logger
            ->info("Captcha is disabled by flag, abstaining from check", Argument::cetera())
            ->shouldBeCalledOnce();

        $stepHelper = $this->makeProphesizedMuted(CaptchaStepHelper::class, [
            '$recaptchaEnabled' => false,
            LoggerInterface::class => $logger->reveal(),
            ClockInterface::class => new ClockTest(),
        ]);
        $step = new UnconditionallySupportingRecaptchaStep();

        $this->assertFalse($stepHelper->supports($step, $credentials));
    }

    public function testEnabledWithNoUnderSiegeModeRecaptchaCheck()
    {
        $credentials = new Credentials(
            new StepData(),
            new Request()
        );
        $logger = $this->prophesize(LoggerInterface::class);
        $logger
            ->info("Captcha is disabled by flag, abstaining from check", Argument::cetera())
            ->shouldBeCalledOnce();

        $stepHelper = $this->makeProphesizedMuted(CaptchaStepHelper::class, [
            '$recaptchaEnabled' => false,
            LoggerInterface::class => $logger->reveal(),
            ClockInterface::class => new ClockTest(),
        ]);
        $step = new UnconditionallySupportingRecaptchaStep();

        $this->assertFalse($stepHelper->supports($step, $credentials));
    }

    public function emptyRecaptchaDataProvider()
    {
        return [
            'empty string' => [''],
            'null' => [null],
        ];
    }

    /**
     * @dataProvider emptyRecaptchaDataProvider
     */
    public function testEmptyRecaptchaProvided($recaptchaProvided)
    {
        $request = new Request();
        $session = $this->prophesize(Session::class);
        $session
            ->has(UnconditionallySupportingRecaptchaStep::ID)
            ->willReturn(false)
            ->shouldBeCalledOnce();

        $credentials = new Credentials(
            (new StepData())
                ->setRecaptcha($recaptchaProvided)
                ->setLogin($loginProvided = 'somelogin'),
            $request
        );
        $request->setSession($session->reveal());

        $logger = $this->prophesize(LoggerInterface::class);
        $logger
            ->info("Captcha required", Argument::cetera())
            ->shouldBeCalledOnce();

        $stepHelper = $this->makeStepWithEnabledRecaptcha([
            LoggerInterface::class => $logger->reveal(),
        ]);
        $step = new UnconditionallySupportingRecaptchaStep();
        $this->assertTrue($stepHelper->supports($step, $credentials));
        $this->expectStepRequiredException("invalid_captcha");
        $stepHelper->doCheck(
            $step,
            $credentials,
            $this->prophesize(CaptchaProviderInterface::class)->reveal()
        );
    }

    public function getStoredResponseCheckDataProvider()
    {
        return [
            'success check' => [
                '$cacheData' => [
                    'client_recaptcha_secret' => 'somerandom',
                    'client_recaptcha_time' => -2,
                    'client_recaptcha_ip' => '10.10.10.10',
                    'client_recaptcha_login' => 'somelogin',
                ],
                '$loggerCalls' => [
                    ['info', ["Captcha stored check succeeded", Argument::type('array')]],
                ],
                '$requiredError' => false,
                '$clearStored' => false,
            ],

            'response expired' => [
                '$cacheData' => [
                    'client_recaptcha_secret' => 'somerandom',
                    'client_recaptcha_time' => -60 * 30,
                    'client_recaptcha_ip' => '10.10.10.10',
                    'client_recaptcha_login' => 'somelogin',
                ],
                '$loggerCalls' => [
                    ['info', ["Captcha stored check failed, response expired", Argument::type('array')]],
                ],
                '$requiredError' => true,
                '$clearStored' => true,
            ],

            'changed login' => [
                '$cacheData' => [
                    'client_recaptcha_secret' => 'somerandom',
                    'client_recaptcha_time' => -2,
                    'client_recaptcha_ip' => '10.10.10.10',
                    'client_recaptcha_login' => 'someloginchanged',
                ],
                '$loggerCalls' => [
                    ['info', ["Captcha stored check failed, login differs from presented", Argument::type('array')]],
                ],
                '$requiredError' => true,
                '$clearStored' => true,
            ],

            'changed ip' => [
                '$cacheData' => [
                    'client_recaptcha_secret' => 'somerandom',
                    'client_recaptcha_time' => -2,
                    'client_recaptcha_ip' => '10.10.10.99',
                    'client_recaptcha_login' => 'somelogin',
                ],
                '$loggerCalls' => [
                    ['warning', ["Captcha stored check failed, ip differs from presented", Argument::type('array')]],
                ],
                '$requiredError' => true,
                '$clearStored' => true,
            ],
        ];
    }

    /**
     * @dataProvider getStoredResponseCheckDataProvider
     */
    public function testStoredResponseCheck(array $cacheData, array $loggerCalls, bool $requiredError, bool $clearStored)
    {
        $request = new Request([], [], [], [], [], [
            'REMOTE_ADDR' => $ip = '10.10.10.10',
        ]);
        $credentials = new Credentials(
            (new StepData())
                ->setRecaptcha($recaptchaProvided = 'somerecpachta')
                ->setLogin($loginProvided = 'somelogin'),
            $request
        );
        $session = $this->prophesize(Session::class);
        $session
            ->has(UnconditionallySupportingRecaptchaStep::ID)
            ->willReturn(true)
            ->shouldBeCalledOnce();
        $session
            ->get(UnconditionallySupportingRecaptchaStep::ID)
            ->willReturn($cacheData)
            ->shouldBeCalledOnce();

        if ($clearStored) {
            $session
                ->remove(UnconditionallySupportingRecaptchaStep::ID)
                ->shouldBeCalledOnce();
        }

        $request->setSession($session->reveal());
        $stepHelper = $this->makeStepWithEnabledRecaptcha([
            LoggerInterface::class => $this->makeLoggerMock($loggerCalls),
            ClockInterface::class => new ClockTest(),
        ]);
        $step = new UnconditionallySupportingRecaptchaStep();
        $this->assertTrue($stepHelper->supports($step, $credentials));

        if ($requiredError) {
            $this->expectStepRequiredException("invalid_captcha");
        }

        $stepHelper->doCheck(
            $step,
            $credentials,
            $this->prophesize(CaptchaProviderInterface::class)->reveal()
        );
    }

    protected function makeStepWithEnabledRecaptcha(array $mixin): CaptchaStepHelper
    {
        if (!isset($mixin[SiegeModeDetector::class])) {
            $siegeMode = $this->prophesize(SiegeModeDetector::class);
            $siegeMode
                ->isUnderSiege()
                ->willReturn(true)->shouldBeCalledOnce();

            return $this->makeStepWithEnabledRecaptcha(\array_merge($mixin, [
                SiegeModeDetector::class => $siegeMode->reveal(),
            ]));
        }

        return $this->makeProphesizedMuted(
            CaptchaStepHelper::class,
            \array_merge(
                [
                    '$recaptchaEnabled' => true,
                ],
                $mixin
            )
        );
    }

    protected function makeCaptchaProviderMock(string $siteKey): CaptchaProviderInterface
    {
        return $this
            ->prophesize(CaptchaProviderInterface::class)

            ->getSiteKey()
            ->willReturn($siteKey)
            ->getObjectProphecy()

            ->getVendor()
            ->willReturn('stub')
            ->getObjectProphecy()

            ->getScriptUrl(Argument::cetera())
            ->willReturn('someurl')
            ->getObjectProphecy()

            ->getScriptUrl(Argument::cetera())
            ->willReturn('someurl')
            ->getObjectProphecy()

            ->reveal();
    }
}
