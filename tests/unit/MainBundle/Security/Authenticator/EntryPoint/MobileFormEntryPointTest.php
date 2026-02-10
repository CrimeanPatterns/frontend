<?php

namespace AwardWallet\Tests\Unit\MainBundle\Security\Authenticator\EntryPoint;

use AwardWallet\MainBundle\Globals\ApiVersioning\ApiVersioningService;
use AwardWallet\MainBundle\Globals\ApiVersioning\MobileVersions;
use AwardWallet\MainBundle\Globals\FormDehydrator;
use AwardWallet\MainBundle\Security\Authenticator\EntryPoint\MobileFormEntryPoint;
use AwardWallet\MainBundle\Security\Authenticator\Step\Exception\ErrorStepAuthenticationException;
use AwardWallet\MainBundle\Security\Authenticator\Step\Exception\RequiredStepAuthenticationException;
use AwardWallet\MainBundle\Security\Authenticator\Step\SecurityQuestion\Question;
use AwardWallet\MainBundle\Security\Authenticator\Step\StepInterface;
use AwardWallet\Tests\Unit\BaseTest;
use AwardWallet\Tests\Unit\MakePostfixTranslatorMockTrait;
use Prophecy\Argument;
use Prophecy\Prediction\NoCallsPrediction;
use Symfony\Component\EventDispatcher\EventDispatcher;
use Symfony\Component\Form\Form;
use Symfony\Component\Form\FormConfigBuilder;
use Symfony\Component\Form\FormFactory;
use Symfony\Component\Form\FormFactoryInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Security\Core\Exception\AuthenticationException;
use Symfony\Component\Security\Core\Exception\UsernameNotFoundException;
use Symfony\Contracts\Translation\TranslatorInterface;

use function AwardWallet\MainBundle\Globals\Utils\iterFluent\it;

/**
 * @group security
 * @group frontend-unit
 * @coversDefaultClass \AwardWallet\MainBundle\Security\Authenticator\EntryPoint\MobileFormEntryPoint
 */
class MobileFormEntryPointTest extends BaseTest
{
    use MakeStepMockWithIdTrait;
    use MakePostfixTranslatorMockTrait;
    use MakeCaptchaProviderMockTrait;

    private const ARG_STEP_ID = '$stepId';
    private const ARG_ERROR_CONTAINS = '$errorContains';
    private const ARG_EXCEPTION_FUNC = '$exceptionFunc';

    private const ARG_SUPPORTS_V4_LOGIN = '$supportsV4Login';
    private const ARG_REQUEST_URI = '$requestUri';
    private const ARG_EXPECTED_RESULT = '$expectedResult';

    public function testCredentialsExtraction()
    {
        $entryPoint = $this->makeEntryPointMuted();

        $request = new Request([],
            [
                'recaptcha' => $recaptcha = 'somerecaptcha',
                '_csrf_token' => $csrfToken = 'somecsrftoken',
                'login_password' => [
                    'login' => $login = 'somelogin',
                    'pass' => $password = 'somepassword',
                ],
                'one_time_code_by_email' => $oneTimeCodeEmail = 'someotcemail',
                'one_time_code_by_app' => $oneTimeCodeByApp = 'someotcapp',
                'one_time_code_app_recovery' => $oneTimeCodeAppRecovery = 'someotcapprecovery',
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
        $this->assertEquals($csrfToken, $stepData->getCsrfToken());
        $this->assertEquals($login, $stepData->getLogin());
        $this->assertEquals($password, $stepData->getPassword());
        $this->assertEquals($oneTimeCodeEmail, $stepData->getOtcEmailCode());
        $this->assertEquals($oneTimeCodeByApp, $stepData->getOtcAppCode());
        $this->assertEquals($oneTimeCodeAppRecovery, $stepData->getOtcRecoveryCode());
        $this->assertEquals($scripted, $stepData->getScripted());
    }

    public function getQuestionsExtractionDataProvider(): array
    {
        return [
            'single' => [
                '$data' => [
                    'question' => $question = 'question',
                    'answer' => $answer = 'answer',
                ],
                '$assert' => [new Question($question, $answer)],
            ],
            'multiple' => [
                '$data' => [
                    $question1 = 'question1' => $answer1 = 'answer1',

                    $question2 = 'question2' => $answer2 = 'answer2',
                ],
                '$answer' => [
                    new Question($question1, $answer1),
                    new Question($question2, $answer2),
                ],
            ],
        ];
    }

    /**
     * @dataProvider getQuestionsExtractionDataProvider
     */
    public function testQuestionsExtraction(array $data, array $expectedQuestions)
    {
        $entryPoint = $this->makeEntryPointMuted();

        $request = new Request([], ['security_question' => $data]);

        $credentials = $entryPoint->getCredentials($request);
        $stepData = $credentials->getStepData();

        $this->assertEquals($expectedQuestions, $stepData->getQuestions());
    }

    /**
     * @dataProvider getFailureHandlingDataProvider
     */
    public function testFailureHandling(string $stepId, array $errorContains, callable $exceptionFunc)
    {
        $formFactory = $this->prophesize(FormFactory::class);
        $formFactory
            ->create(Argument::cetera())
            ->willReturn(new Form(new FormConfigBuilder('some', null, new EventDispatcher())));

        $formDehydrator = $this->prophesize(FormDehydrator::class);
        $formDehydrator
            ->dehydrateForm(Argument::cetera())
            ->willReturn(['children' => []]);

        $apiVersioning = $this->prophesize(ApiVersioningService::class);
        $apiVersioning
            ->supports(MobileVersions::LOGIN_CHECK_EMAIL_OTC_STEP_LABELS)
            ->willReturn(true);

        $entryPoint = $this->makeEntryPointMuted([
            TranslatorInterface::class => $this->makePostfixTranslatorMockTrait(),
            FormFactoryInterface::class => $formFactory->reveal(),
            FormDehydrator::class => $formDehydrator->reveal(),
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
                    self::ARG_ERROR_CONTAINS => ['error' => 'Some Error###translated'],
                    self::ARG_EXCEPTION_FUNC => function () { return new AuthenticationException('Some Error'); },
                ],
                'user not found error' => [
                    self::ARG_STEP_ID => 'somestep',
                    self::ARG_ERROR_CONTAINS => [
                        'login_password' => ['error' => 'Bad credentials###translated'],
                    ],
                    self::ARG_EXCEPTION_FUNC => function () { return new UsernameNotFoundException('Some exception'); },
                ],
                'invalid login/pass' => [
                    self::ARG_STEP_ID => 'login_password',
                    self::ARG_ERROR_CONTAINS => [
                        'login_password' => ['error' => 'Bad credentials###translated'],
                    ],
                    self::ARG_EXCEPTION_FUNC => function (StepInterface $step) {
                        return new ErrorStepAuthenticationException($step, 'Some Error!');
                    },
                ],
                'invalid OTC by app' => [
                    self::ARG_STEP_ID => 'one_time_code_by_app',
                    self::ARG_ERROR_CONTAINS => [
                        'one_time_code_by_app' => [
                            'label' => "login.otc###translated",
                            'error' => 'Some OTC Error###translated',
                            'hint' => "login.bottom.otc-recovery.hint###translated",
                            'notice' => 'error.auth.two-factor.code-required###translated',
                        ],
                    ],
                    self::ARG_EXCEPTION_FUNC => function (StepInterface $step) {
                        return new ErrorStepAuthenticationException($step, [], 'Some OTC Error');
                    },
                ],
                'required OTC by app' => [
                    self::ARG_STEP_ID => 'one_time_code_by_app',
                    self::ARG_ERROR_CONTAINS => [
                        'one_time_code_by_app' => [
                            'label' => "login.otc###translated",
                            'hint' => "login.bottom.otc-recovery.hint###translated",
                            'notice' => 'error.auth.two-factor.code-required###translated',
                        ],
                    ],
                    self::ARG_EXCEPTION_FUNC => function (StepInterface $step) {
                        return new RequiredStepAuthenticationException($step, [], 'Some OTC Error');
                    },
                ],
                'invalid security question' => [
                    self::ARG_STEP_ID => 'security_question',
                    self::ARG_ERROR_CONTAINS => [
                        'security_question' => [
                            'form' => ['children' => []],
                            'error' => 'Some OTC Error###translated',
                        ],
                    ],
                    self::ARG_EXCEPTION_FUNC => function (StepInterface $step) {
                        return new ErrorStepAuthenticationException($step, [], 'Some OTC Error');
                    },
                ],
                'required security question' => [
                    self::ARG_STEP_ID => 'security_question',
                    self::ARG_ERROR_CONTAINS => [
                        'security_question' => [
                            'form' => ['children' => []],
                            'notice' => 'Some OTC Error###translated',
                        ],
                    ],
                    self::ARG_EXCEPTION_FUNC => function (StepInterface $step) {
                        return new RequiredStepAuthenticationException($step, [], 'Some OTC Error');
                    },
                ],
                'invalid OTC by email' => [
                    self::ARG_STEP_ID => 'one_time_code_by_email',
                    self::ARG_ERROR_CONTAINS => [
                        'one_time_code_by_email' => [
                            'notice' => 'login.otc.label###translated',
                            'error' => 'Some OTC Error###translated',
                            'hint' => 'error.auth.two-factor.email-code-required###translated',
                        ],
                    ],
                    self::ARG_EXCEPTION_FUNC => function (StepInterface $step) {
                        return new ErrorStepAuthenticationException($step, [], 'Some OTC Error');
                    },
                ],
                'required OTC by email' => [
                    self::ARG_STEP_ID => 'one_time_code_by_email',
                    self::ARG_ERROR_CONTAINS => [
                        'one_time_code_by_email' => [
                            'notice' => 'login.otc.label###translated',
                            'hint' => 'error.auth.two-factor.email-code-required###translated',
                        ],
                    ],
                    self::ARG_EXCEPTION_FUNC => function (StepInterface $step) {
                        return new RequiredStepAuthenticationException($step, [], 'Some OTC Error');
                    },
                ],
            ],
            it([
                'login_password_lockout',
                'business_admin',
                'ip_locker',
                'csrf',
                'scripted',
            ])
                ->flatMap(function (string $stepId) {
                    yield "Error for step: $stepId" => [
                        self::ARG_STEP_ID => $stepId,
                        self::ARG_ERROR_CONTAINS => ['error' => 'Error message###translated'],
                        self::ARG_EXCEPTION_FUNC => function (StepInterface $step) {
                            return new ErrorStepAuthenticationException($step, [], 'Error message');
                        },
                    ];

                    yield "Required for step: $stepId" => [
                        self::ARG_STEP_ID => $stepId,
                        self::ARG_ERROR_CONTAINS => ['error' => 'Required message###translated'],
                        self::ARG_EXCEPTION_FUNC => function (StepInterface $step) {
                            return new RequiredStepAuthenticationException($step, [], 'Required message');
                        },
                    ];
                })
                ->toArrayWithKeys(),
            it([
                'mobile_captcha',
            ])
                ->flatMap(function (string $stepId) {
                    yield "Error for step: $stepId" => [
                        self::ARG_STEP_ID => $stepId,
                        self::ARG_ERROR_CONTAINS => [
                            'recaptcha' => [
                                'key' => 'somekey',
                                'error' => true,
                            ],
                        ],
                        self::ARG_EXCEPTION_FUNC => function (StepInterface $step) {
                            return new ErrorStepAuthenticationException(
                                $step,
                                $this->makeCaptchaProviderMock('somekey')->reveal(),
                                'Error message'
                            );
                        },
                    ];

                    yield "Required for step: $stepId" => [
                        self::ARG_STEP_ID => $stepId,
                        self::ARG_ERROR_CONTAINS => [
                            'recaptcha' => [
                                'key' => 'somekey',
                                'required' => true,
                            ],
                        ],
                        self::ARG_EXCEPTION_FUNC => function (StepInterface $step) {
                            return new RequiredStepAuthenticationException(
                                $step,
                                $this->makeCaptchaProviderMock('somekey')->reveal(),
                                'Required message'
                            );
                        },
                    ];
                })
                ->toArrayWithKeys()
        );
    }

    public function getMobileClientMatchDataProvider()
    {
        return [
            'client supports v4 login, mobile route' => [
                self::ARG_SUPPORTS_V4_LOGIN => true,
                self::ARG_REQUEST_URI => '/m/api/some',
                self::ARG_EXPECTED_RESULT => true,
            ],
            'client support v4 login, other route' => [
                self::ARG_SUPPORTS_V4_LOGIN => true,
                self::ARG_REQUEST_URI => '/account/some',
                self::ARG_EXPECTED_RESULT => false,
            ],
            'client does not support v4 login, mobile route' => [
                self::ARG_SUPPORTS_V4_LOGIN => false,
                self::ARG_REQUEST_URI => '/m/api/some',
                self::ARG_EXPECTED_RESULT => false,
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
            ->supports(MobileVersions::DETAILED_LOGIN_CHECK_RESPONSE)
            ->willReturn($supportsV4Login);

        $entryPoint = $this->makeEntryPointMuted([
            ApiVersioningService::class => $apiVersioning->reveal(),
        ]);
        $request = new Request([], [], [], [], [], [
            'REQUEST_URI' => $requestUri,
        ]);
        $this->assertEquals($expectedResult, $entryPoint->supportsArea($request));
    }

    protected function makeStepMockWithId(string $id): StepInterface
    {
        $step = $this->prophesizeExtended(StepInterface::class);
        $step
            ->getId()
            ->willReturn($id);

        $step->prophesizeRemainingMethods(new NoCallsPrediction());

        return $step->reveal();
    }

    protected function makeEntryPointMuted(array $argsMixinMap = []): MobileFormEntryPoint
    {
        return $this->makeProphesizedMuted(
            MobileFormEntryPoint::class,
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
