<?php

namespace AwardWallet\Tests\Unit\MainBundle\Security\Authenticator\EntryPoint;

use AwardWallet\MainBundle\Globals\ApiVersioning\ApiVersioningService;
use AwardWallet\MainBundle\Globals\ApiVersioning\MobileVersions;
use AwardWallet\MainBundle\Security\Authenticator\EntryPoint\MobileOldFormEntryPoint;
use AwardWallet\MainBundle\Security\Authenticator\Step\Exception\ErrorStepAuthenticationException;
use AwardWallet\MainBundle\Security\Authenticator\Step\Exception\RequiredStepAuthenticationException;
use AwardWallet\MainBundle\Security\Authenticator\Step\StepInterface;
use AwardWallet\Tests\Unit\BaseTest;
use AwardWallet\Tests\Unit\MakePostfixTranslatorMockTrait;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Security\Core\Exception\AuthenticationException;
use Symfony\Component\Security\Core\Exception\UsernameNotFoundException;
use Symfony\Contracts\Translation\TranslatorInterface;

use function AwardWallet\MainBundle\Globals\Utils\iterFluent\it;

/**
 * @group security
 * @group frontend-unit
 * @coversDefaultClass \AwardWallet\MainBundle\Security\Authenticator\EntryPoint\MobileOldFormEntryPoint
 */
class MobileOldFormEntryPointTest extends BaseTest
{
    use MakePostfixTranslatorMockTrait;
    use MakeStepMockWithIdTrait;
    use MakeCaptchaProviderMockTrait;

    private const ARG_SUPPORTS_V4_LOGIN = '$supportsV4Login';
    private const ARG_REQUEST_URI = '$requestUri';
    private const ARG_EXPECTED_RESULT = '$expectedResult';

    private const ARG_STEP_ID = '$stepId';
    private const ARG_ERROR_CONTAINS = '$errorContains';
    private const ARG_EXCEPTION_FUNC = '$exceptionFunc';

    public function testCredentialsExtraction()
    {
        $entryPoint = $this->makeEntryPointMuted();

        $request = new Request([],
            [
                'recaptcha' => $recaptcha = 'somerecaptcha',
                'login' => $login = 'somelogin',
                'password' => $password = 'somepassword',
                '_otc' => $oneTimeCodeEmail = $oneTimeCodeByApp = 'someotcemail',
            ],
            [],
            [],
            [],
            [
                'HTTP_X_SCRIPTED' => $scripted = 'somescripted',
            ]
        );

        $credentials = $entryPoint->getCredentials($request);
        $stepData = $credentials->getStepData();

        $this->assertEquals($recaptcha, $stepData->getRecaptcha());
        $this->assertEquals($login, $stepData->getLogin());
        $this->assertEquals($password, $stepData->getPassword());
        $this->assertEquals($oneTimeCodeEmail, $stepData->getOtcEmailCode());
        $this->assertEquals($oneTimeCodeByApp, $stepData->getOtcAppCode());
        $this->assertEquals($scripted, $stepData->getScripted());
    }

    public function getMobileClientMatchDataProvider()
    {
        return [
            'client supports v4 login, mobile route' => [
                self::ARG_SUPPORTS_V4_LOGIN => true,
                self::ARG_REQUEST_URI => '/m/api/some',
                self::ARG_EXPECTED_RESULT => false,
            ],
            'client support v4 login, other route' => [
                self::ARG_SUPPORTS_V4_LOGIN => true,
                self::ARG_REQUEST_URI => '/account/some',
                self::ARG_EXPECTED_RESULT => false,
            ],
            'client does not support v4 login, mobile route' => [
                self::ARG_SUPPORTS_V4_LOGIN => false,
                self::ARG_REQUEST_URI => '/m/api/some',
                self::ARG_EXPECTED_RESULT => true,
            ],
            'client does not support v4 login, other route' => [
                self::ARG_SUPPORTS_V4_LOGIN => false,
                self::ARG_REQUEST_URI => '/account/some',
                self::ARG_EXPECTED_RESULT => false,
            ],
        ];
    }

    /**
     * @dataProvider getMobileClientMatchDataProvider
     */
    public function testMobileClientMatch(bool $supportsV4Login, string $requestUri, bool $expectedResult)
    {
        $apiVersioning = $this->prophesize(ApiVersioningService::class);
        $apiVersioning
            ->notSupports(MobileVersions::DETAILED_LOGIN_CHECK_RESPONSE)
            ->willReturn(!$supportsV4Login);

        $entryPoint = $this->makeEntryPointMuted([
            ApiVersioningService::class => $apiVersioning->reveal(),
        ]);
        $request = new Request([], [], [], [], [], [
            'REQUEST_URI' => $requestUri,
        ]);
        $this->assertEquals($expectedResult, $entryPoint->supportsArea($request));
    }

    /**
     * @dataProvider getFailureHandlingDataProvider
     */
    public function testFailureHandling(string $stepId, array $errorContains, callable $exceptionFunc)
    {
        $apiVersioning = $this->prophesize(ApiVersioningService::class);
        $apiVersioning
            ->supports(MobileVersions::LOGIN_CHECK_EMAIL_OTC_STEP_LABELS)
            ->willReturn(true);

        $entryPoint = $this->makeEntryPointMuted([
            TranslatorInterface::class => $this->makePostfixTranslatorMockTrait(),
            ApiVersioningService::class => $apiVersioning->reveal(),
        ]);
        $request = new Request([], []);
        $exception = $exceptionFunc($this->makeStepMockWithId($stepId));

        /** @var JsonResponse $response */
        $response = $entryPoint->onAuthenticationFailure(
            $request,
            $exception
        );
        $this->assertInstanceOf(JsonResponse::class, $response);
        $this->assertArrayContainsArray(
            \array_merge(
                ['success' => false],
                $errorContains
            ),
            \json_decode($response->getContent(), true)
        );
    }

    public function getFailureHandlingDataProvider(): array
    {
        return \array_merge(
            [
                'generic error' => [
                    self::ARG_STEP_ID => 'somestep',
                    self::ARG_ERROR_CONTAINS => ['message' => 'Some Error###translated'],
                    self::ARG_EXCEPTION_FUNC => function () { return new AuthenticationException('Some Error'); },
                ],
                'user not found error' => [
                    self::ARG_STEP_ID => 'somestep',
                    self::ARG_ERROR_CONTAINS => [
                        'badCredentials' => true,
                        'message' => 'Bad credentials###translated',
                    ],
                    self::ARG_EXCEPTION_FUNC => function () { return new UsernameNotFoundException('Some exception'); },
                ],
                'invalid OTC by app' => [
                    self::ARG_STEP_ID => 'one_time_code_by_app',
                    self::ARG_ERROR_CONTAINS => [
                        'otcInputLabel' => "login.otc###translated",
                        'otcInputHint' => "login.bottom.otc-recovery.hint###translated",
                        'otcRequired' => true,
                        'otcShowRecovery' => true,
                        'message' => 'Some OTC Error###translated',
                    ],
                    self::ARG_EXCEPTION_FUNC => function (StepInterface $step) {
                        return new RequiredStepAuthenticationException($step, [], 'Some OTC Error');
                    },
                ],
                'invalid OTC by email' => [
                    self::ARG_STEP_ID => 'one_time_code_by_email',
                    self::ARG_ERROR_CONTAINS => [
                        'otcInputLabel' => "login.otc.label###translated",
                        'otcRequired' => true,
                        'otcShowRecovery' => true,
                        'message' => 'Some OTC Error###translated',
                    ],
                    self::ARG_EXCEPTION_FUNC => function (StepInterface $step) {
                        return new RequiredStepAuthenticationException($step, [], 'Some OTC Error');
                    },
                ],
                'invalid recaptcha' => [
                    self::ARG_STEP_ID => 'mobile_captcha',
                    self::ARG_ERROR_CONTAINS => [
                        'recaptchaRequired' => true,
                        'message' => 'Some Error###translated',
                    ],
                    self::ARG_EXCEPTION_FUNC => function (StepInterface $step) {
                        return new RequiredStepAuthenticationException(
                            $step,
                            $this->makeCaptchaProviderMock()->reveal(),
                            'Some Error'
                        );
                    },
                ],
            ],
            it([
                'login_password_lockout',
                'ip_locker',
                'scripted',
                'login_password',
            ])
                ->flatMap(function (string $stepId) {
                    yield "Error for step: $stepId" => [
                        self::ARG_STEP_ID => $stepId,
                        self::ARG_ERROR_CONTAINS => [
                            'otcRequired' => false,
                            'badCredentials' => true,
                            'message' => 'Error message###translated',
                        ],
                        self::ARG_EXCEPTION_FUNC => function (StepInterface $step) {
                            return new ErrorStepAuthenticationException($step, [], 'Error message');
                        },
                    ];
                })
                ->toArrayWithKeys()
        );
    }

    protected function makeEntryPointMuted(array $argsMixinMap = []): MobileOldFormEntryPoint
    {
        return $this->makeProphesizedMuted(
            MobileOldFormEntryPoint::class,
            \array_merge(
                [
                    '$steps' => [],
                    '$stepFactories' => [],
                ],
                $argsMixinMap
            )
        );
    }
}
