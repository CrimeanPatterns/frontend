<?php

namespace AwardWallet\MainBundle\Security\Authenticator\EntryPoint;

use AwardWallet\MainBundle\Form\Extension\JsonFormExtension\JsonRequestHandler;
use AwardWallet\MainBundle\Form\Type\SecurityQuestionType;
use AwardWallet\MainBundle\Globals\ApiVersioning\ApiVersioningService;
use AwardWallet\MainBundle\Globals\ApiVersioning\MobileVersions;
use AwardWallet\MainBundle\Globals\FormDehydrator;
use AwardWallet\MainBundle\Globals\StringUtils;
use AwardWallet\MainBundle\Security\Authenticator\Credentials;
use AwardWallet\MainBundle\Security\Authenticator\Step\BusinessAdminStep;
use AwardWallet\MainBundle\Security\Authenticator\Step\CallableStep;
use AwardWallet\MainBundle\Security\Authenticator\Step\CsrfStep;
use AwardWallet\MainBundle\Security\Authenticator\Step\Exception\ErrorStepAuthenticationException;
use AwardWallet\MainBundle\Security\Authenticator\Step\Exception\RequiredStepAuthenticationException;
use AwardWallet\MainBundle\Security\Authenticator\Step\Exception\StepAuthenticationExceptionInterface;
use AwardWallet\MainBundle\Security\Authenticator\Step\IpLockoutStep;
use AwardWallet\MainBundle\Security\Authenticator\Step\LoadUserStep;
use AwardWallet\MainBundle\Security\Authenticator\Step\LoginPasswordLockoutStep;
use AwardWallet\MainBundle\Security\Authenticator\Step\LoginPasswordStep;
use AwardWallet\MainBundle\Security\Authenticator\Step\OneTimeCodeByAppRecoveryStep;
use AwardWallet\MainBundle\Security\Authenticator\Step\OneTimeCodeByAppStep;
use AwardWallet\MainBundle\Security\Authenticator\Step\OneTimeCodeByEmail\OneTimeCodeByEmailStep;
use AwardWallet\MainBundle\Security\Authenticator\Step\Recaptcha\MobileCaptchaStep;
use AwardWallet\MainBundle\Security\Authenticator\Step\ScriptedStep;
use AwardWallet\MainBundle\Security\Authenticator\Step\SecurityQuestion\Question;
use AwardWallet\MainBundle\Security\Authenticator\Step\SecurityQuestion\SecurityQuestionsStep;
use AwardWallet\MainBundle\Security\Authenticator\Step\StepData;
use AwardWallet\MainBundle\Security\Captcha\Provider\CaptchaProviderInterface;
use Psr\Log\LoggerInterface;
use Psr\Log\LogLevel;
use Symfony\Component\Form\FormFactoryInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestMatcher;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Exception\AuthenticationException;
use Symfony\Component\Security\Core\Exception\UsernameNotFoundException;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Security\Core\User\UserProviderInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

class MobileFormEntryPoint extends AbstractLoginEntryPoint
{
    /**
     * @var ApiVersioningService
     */
    protected $apiVersioning;
    /**
     * @var TranslatorInterface
     */
    protected $translator;
    /**
     * @var FormFactoryInterface
     */
    protected $formFactory;
    /**
     * @var FormDehydrator
     */
    protected $formDehydrator;
    /**
     * @var FormEntryPointHelper
     */
    protected $formEntryPointHelper;
    /**
     * @var LoggerInterface
     */
    protected $logger;
    /**
     * @var AuthKeyChecker
     */
    protected $authKeyChecker;

    public function __construct(
        iterable $steps,
        iterable $stepFactories,
        FormEntryPointHelper $formEntryPointHelper,
        TranslatorInterface $translator,
        FormFactoryInterface $formFactory,
        FormDehydrator $formDehydrator,
        ApiVersioningService $apiVersioning,
        AuthKeyChecker $authKeyChecker,
        LoggerInterface $logger
    ) {
        $this->steps = $steps;
        $this->formEntryPointHelper = $formEntryPointHelper;
        $this->apiVersioning = $apiVersioning;
        $this->translator = $translator;
        $this->formFactory = $formFactory;
        $this->formDehydrator = $formDehydrator;
        $this->logger = $logger;
        $this->stepFactories = $stepFactories;
        $this->authKeyChecker = $authKeyChecker;
    }

    public function getId(): string
    {
        return 'mobile';
    }

    public function loadUserSteps(Credentials $credentials, UserProviderInterface $userProvider): ?UserInterface
    {
        $this->getStep(IpLockoutStep::ID)->check($credentials);
        $this->getStep(MobileCaptchaStep::ID)->check($credentials);
        $this->getStep(LoadUserStep::ID, $userProvider)->check($credentials);

        return $credentials->getUser();
    }

    public function postLoadUserSteps(Credentials $credentials, UserInterface $user): bool
    {
        $this->getStep(LoginPasswordLockoutStep::ID)->check($credentials);
        $this->getStep(ScriptedStep::ID)->check($credentials);
        $this->getStep(LoginPasswordStep::ID)->check($credentials);
        $this->getStep(BusinessAdminStep::ID)->check($credentials);

        $secondFactorStepChain = $this
            ->getStep(OneTimeCodeByAppStep::ID)
            ->otherwise(new CallableStep(function () use ($credentials): bool {
                if ($this->authKeyChecker->keyExists($credentials)) {
                    return false;
                }

                $stepChain = $this
                    ->getStep(SecurityQuestionsStep::ID)
                    ->otherwise($this->getStep(OneTimeCodeByEmailStep::ID));

                return $stepChain->check($credentials);
            }));

        $secondFactorStepChain->check($credentials);

        return true;
    }

    public function getCredentials(Request $request): Credentials
    {
        $stepData = new StepData();

        if ($json = JsonRequestHandler::parse($request)) {
            $request->request->replace($json);
        }

        $request->request->set('_remember_me', '1');

        if (
            $request->request->has('recaptcha')
            && StringUtils::isNotEmpty($recaptcha = $request->request->get('recaptcha'))
        ) {
            $stepData->setRecaptcha($recaptcha);
        }

        if ($request->request->has('_csrf_token')) {
            $stepData->setCsrfToken($request->request->get('_csrf_token'));
        }

        if ($request->request->has(LoginPasswordStep::ID)) {
            $loginPassword = $request->request->get(LoginPasswordStep::ID);
            $stepData
                ->setLogin($loginPassword['login'] ?? null)
                ->setPassword($loginPassword['pass'] ?? null);
        }

        if ($request->request->has(OneTimeCodeByEmailStep::ID)) {
            $stepData->setOtcEmailCode($request->request->get(OneTimeCodeByEmailStep::ID));
        }

        if ($request->request->has(OneTimeCodeByAppStep::ID)) {
            $stepData->setOtcAppCode($request->request->get(OneTimeCodeByAppStep::ID));
        }

        if ($request->request->has(OneTimeCodeByAppRecoveryStep::ID)) {
            $stepData->setOtcRecoveryCode($request->request->get(OneTimeCodeByAppRecoveryStep::ID));
        }

        if ($request->headers->has('X-Scripted')) {
            $stepData->setScripted($request->headers->get('X-Scripted'));
        }

        if ($request->request->has(SecurityQuestionsStep::ID)) {
            $questionData = $request->request->get(SecurityQuestionsStep::ID);
            $questions = [];

            if (isset($questionData['question'], $questionData['answer'])) {
                $questions[] = new Question($questionData['question'], $questionData['answer']);
            } else {
                foreach ($questionData as $question => $answer) {
                    if (\is_scalar($answer)) {
                        $questions[] = new Question((string) $question, (string) $answer);
                    }
                }
            }

            $stepData->setQuestions($questions);
        }

        return new Credentials($stepData, $request);
    }

    public function onAuthenticationFailure(Request $request, AuthenticationException $exception): ?Response
    {
        parent::onAuthenticationFailure($request, $exception);

        $data = [
            'success' => false,
        ];

        if ($exception instanceof StepAuthenticationExceptionInterface) {
            $stepId = $exception->getStep()->getId();

            $message = $exception->getMessage();
            $message = $this->translator->trans(/** @Ignore */
                $message, [], 'validators');

            $stepData = [];

            if ($exception instanceof ErrorStepAuthenticationException) {
                $stepData['error'] = $message;
            } elseif ($exception instanceof RequiredStepAuthenticationException) {
                $stepData['notice'] = $message;
            }

            switch ($stepId) {
                case LoginPasswordLockoutStep::ID:
                case BusinessAdminStep::ID:
                case IpLockoutStep::ID:
                case CsrfStep::ID:
                case ScriptedStep::ID:
                    $data['error'] = $message;

                    break;

                case LoginPasswordStep::ID:
                    $stepData['error'] = $this->translator->trans('Bad credentials', [], 'validators');
                    $data[$stepId] = $stepData;

                    break;

                case OneTimeCodeByAppStep::ID:
                    $stepData['label'] = $this->translator->trans("login.otc");
                    $stepData['hint'] = $this->translator->trans("login.bottom.otc-recovery.hint");
                    $stepData['notice'] = $this->translator->trans('error.auth.two-factor.code-required');
                    $data[$stepId] = $stepData;

                    break;

                case SecurityQuestionsStep::ID:
                    $questions = $exception->getData();
                    $questionsForm = $this->formFactory->create(SecurityQuestionType::class, $questions,
                        ['csrf_protection' => false]);
                    $stepData['form'] = $this->formDehydrator->dehydrateForm($questionsForm);
                    $data[$stepId] = $stepData;

                    break;

                case OneTimeCodeByEmailStep::ID:
                    if ($this->apiVersioning->supports(MobileVersions::LOGIN_CHECK_EMAIL_OTC_STEP_LABELS)) {
                        $stepData['notice'] = $this->translator->trans("login.otc.label");
                        $stepData['hint'] = $this->translator->trans('error.auth.two-factor.email-code-required');
                    } else {
                        $stepData['label'] = $this->translator->trans("login.otc.label");
                        $stepData['notice'] = $this->translator->trans('error.auth.two-factor.email-code-required');
                    }

                    $data[$stepId] = $stepData;

                    break;

                case MobileCaptchaStep::ID:
                    /** @var CaptchaProviderInterface $captchaProvider */
                    $captchaProvider = $exception->getData();
                    $data['recaptcha'] = \array_merge(
                        [
                            'key' => $captchaProvider->getSiteKey(),
                            'url' => $captchaProvider->getScriptUrl('onLoadCallback'),
                            'vendor' => $captchaProvider->getVendor(),
                        ],
                        $exception instanceof RequiredStepAuthenticationException ?
                            ["required" => true] :
                            ["error" => true]
                    );

                    break;
            }
        } elseif ($exception instanceof UsernameNotFoundException) {
            $data[LoginPasswordStep::ID]['error'] = $this->translator->trans('Bad credentials', [], 'validators');
        } else {
            $data['error'] = $this->translator->trans(/** @Ignore */ $exception->getMessage(), [], 'validators');
        }

        return new JsonResponse($data);
    }

    public function onAuthenticationSuccess(Request $request, TokenInterface $token, $providerKey): ?Response
    {
        parent::onAuthenticationSuccess($request, $token, $providerKey);

        return $this->formEntryPointHelper->onAuthenticationSuccess($request, $token, $providerKey);
    }

    public function start(Request $request, ?AuthenticationException $authException = null): Response
    {
        return new JsonResponse(['error' => $this->translator->trans('access.denied')], 403);
    }

    public function supportsArea(Request $request): bool
    {
        return
            (new RequestMatcher('^/m/api/'))->matches($request)
            && $this->apiVersioning->supports(MobileVersions::DETAILED_LOGIN_CHECK_RESPONSE);
    }

    public function supportsLogin(Request $request): bool
    {
        return $this->matchRequestByRoute($request, 'awm_new_login_check');
    }

    public function supportsRememberMe(Request $request): bool
    {
        return true;
    }

    protected function log(string $message, array $context = [], $level = LogLevel::WARNING): void
    {
        $this->logger->log($level, $message, $context);
    }
}
