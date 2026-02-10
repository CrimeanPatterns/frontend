<?php

namespace AwardWallet\MainBundle\Security\Authenticator\EntryPoint;

use AwardWallet\MainBundle\Globals\StringUtils;
use AwardWallet\MainBundle\Security\Authenticator\Credentials;
use AwardWallet\MainBundle\Security\Authenticator\Step\BusinessAdminStep;
use AwardWallet\MainBundle\Security\Authenticator\Step\CallableStep;
use AwardWallet\MainBundle\Security\Authenticator\Step\CsrfStep;
use AwardWallet\MainBundle\Security\Authenticator\Step\DetectBotIpStep;
use AwardWallet\MainBundle\Security\Authenticator\Step\Exception\AbstractStepAuthenticationException;
use AwardWallet\MainBundle\Security\Authenticator\Step\IpLockoutStep;
use AwardWallet\MainBundle\Security\Authenticator\Step\LoadUserStep;
use AwardWallet\MainBundle\Security\Authenticator\Step\LoginPasswordLockoutStep;
use AwardWallet\MainBundle\Security\Authenticator\Step\LoginPasswordStep;
use AwardWallet\MainBundle\Security\Authenticator\Step\OneTimeCodeByAppRecoveryStep;
use AwardWallet\MainBundle\Security\Authenticator\Step\OneTimeCodeByAppStep;
use AwardWallet\MainBundle\Security\Authenticator\Step\OneTimeCodeByEmail\OneTimeCodeByEmailStep;
use AwardWallet\MainBundle\Security\Authenticator\Step\Recaptcha\DesktopCaptchaStep;
use AwardWallet\MainBundle\Security\Authenticator\Step\ScriptedStep;
use AwardWallet\MainBundle\Security\Authenticator\Step\SecurityQuestion\Question;
use AwardWallet\MainBundle\Security\Authenticator\Step\SecurityQuestion\SecurityQuestionsStep;
use AwardWallet\MainBundle\Security\Authenticator\Step\StepData;
use AwardWallet\MainBundle\Service\MobileAppRedirector;
use Psr\Log\LoggerInterface;
use Psr\Log\LogLevel;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Exception\AuthenticationException;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Security\Core\User\UserProviderInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

class DesktopFormEntryPoint extends AbstractLoginEntryPoint
{
    /**
     * @var FormEntryPointHelper
     */
    protected $formEntryPointHelper;
    /**
     * @var LoggerInterface
     */
    protected $logger;
    /**
     * @var TranslatorInterface
     */
    private $translator;
    /**
     * @var AuthKeyChecker
     */
    private $authKeyChecker;

    private MobileAppRedirector $mobileAppRedirector;

    public function __construct(
        iterable $steps,
        iterable $stepFactories,
        FormEntryPointHelper $formEntryPointHelper,
        TranslatorInterface $translator,
        AuthKeyChecker $authKeyChecker,
        LoggerInterface $logger,
        MobileAppRedirector $mobileAppRedirector
    ) {
        $this->steps = $steps;
        $this->formEntryPointHelper = $formEntryPointHelper;
        $this->translator = $translator;
        $this->logger = $logger;
        $this->stepFactories = $stepFactories;
        $this->authKeyChecker = $authKeyChecker;
        $this->mobileAppRedirector = $mobileAppRedirector;
    }

    public function getId(): string
    {
        return 'desktop';
    }

    public function start(Request $request, ?AuthenticationException $authException = null): Response
    {
        if ($request->isXmlHttpRequest()) {
            return new Response("unauthorized", 403, ["Ajax-Error" => "unauthorized"]);
        } else {
            return $this->getRedirectResponse($request);
        }
    }

    public function supportsArea(Request $request): bool
    {
        return $this->matchRequestByPath($request, '^/?');
    }

    public function loadUserSteps(Credentials $credentials, UserProviderInterface $userProvider): ?UserInterface
    {
        $this->getStep(DesktopCaptchaStep::ID)->check($credentials);
        $this->getStep(IpLockoutStep::ID)->check($credentials);
        $this->getStep(DetectBotIpStep::ID)->check($credentials);
        $this->getStep(LoadUserStep::ID, $userProvider)->check($credentials);

        return $credentials->getUser();
    }

    public function postLoadUserSteps(Credentials $credentials, UserInterface $user): bool
    {
        $this->getStep(CsrfStep::ID)->check($credentials);
        $this->getStep(LoginPasswordLockoutStep::ID)->check($credentials);
        $this->getStep(ScriptedStep::ID)->check($credentials);
        $this->getStep(LoginPasswordStep::ID)->check($credentials);
        $this->getStep(BusinessAdminStep::ID)->check($credentials);

        $secondFactorStepChain = $this
            ->getStep(OneTimeCodeByAppRecoveryStep::ID)
            ->otherwise($this->getStep(OneTimeCodeByAppStep::ID))
            ->otherwise(new CallableStep(function (Credentials $credentials): bool {
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

        $otc = $request->request->get('_otc');
        $recaptcha = $request->request->get('recaptcha');

        $stepData
            ->setLogin($request->request->get('login'))
            ->setPassword($request->request->get('password'))
            ->setOtcAppCode($otc)
            ->setOtcEmailCode($otc)
            ->setScripted($request->request->get('X-Scripted', $request->headers->get('X-Scripted')))
            ->setCsrfToken($request->request->get("_csrf_token"))
            ->setOtcRecoveryCode($request->request->get('_otc_recovery', null))
            ->setRecaptcha(is_string($recaptcha) && StringUtils::isNotEmpty($recaptcha) ? $recaptcha : null)
        ;

        $p = strpos($otc, '=');

        if ($p !== false) {
            $stepData->setQuestions([new Question(substr($otc, 0, $p), substr($otc, $p + 1))]);
        }

        return new Credentials($stepData, $request);
    }

    public function onAuthenticationFailure(Request $request, AuthenticationException $exception): ?Response
    {
        parent::onAuthenticationFailure($request, $exception);

        $response = [
            'success' => false,
            'message' => $this->translator->trans(/** @Ignore */ $exception->getMessage(), [], 'validators'),
            'badCredentials' => true,
            'otcRequired' => false,
        ];

        if ($exception instanceof AbstractStepAuthenticationException) {
            $stepId = $exception->getStep()->getId();

            switch ($stepId) {
                case OneTimeCodeByAppStep::ID:
                    $response['otcRequired'] = true;
                    $response['otcInputLabel'] = $this->translator->trans(/** @Desc("One-time code") */ "login.otc");
                    $response['otcInputHint'] = $this->translator->trans(
                        /** @Desc("(this is a 6-digit code generated by Google Authenticator or equivalent program)") */
                        "login.bottom.otc-recovery.hint"
                    );
                    $response['otcShowRecovery'] = true;
                    $response['badCredentials'] = false;

                    break;

                case SecurityQuestionsStep::ID:
                    $questions = $exception->getData();

                    $response['otcRequired'] = true;
                    $response['otcInputLabel'] = array_values($questions);
                    $response['otcShowRecovery'] = false;
                    $response['badCredentials'] = false;

                    break;

                case OneTimeCodeByEmailStep::ID:
                    $response['otcRequired'] = true;
                    $response['otcInputLabel'] = $this->translator->trans(/** @Desc("One time access code from email") */ "login.otc.label");
                    $response['otcShowRecovery'] = false;
                    $response['badCredentials'] = false;

                    break;

                case DesktopCaptchaStep::ID:
                    $response['recaptchaRequired'] = true;

                    break;
            }
        }

        return new JsonResponse($response);
    }

    public function onAuthenticationSuccess(Request $request, TokenInterface $token, $providerKey): ?Response
    {
        parent::onAuthenticationSuccess($request, $token, $providerKey);

        return $this->formEntryPointHelper->onAuthenticationSuccess($request, $token, $providerKey);
    }

    public function supportsRememberMe(Request $request): bool
    {
        return true;
    }

    public function supportsLogin(Request $request): bool
    {
        return $this->matchRequestByRoute($request, 'aw_users_logincheck');
    }

    protected function log(string $message, array $context = [], $level = LogLevel::WARNING): void
    {
        $this->logger->log($level, $message, $context);
    }

    private function getRedirectResponse(Request $request): Response
    {
        $redirectUrl = '/login?BackTo=' . urlencode($request->getPathInfo() . ($request->getQueryString() ? "?" . $request->getQueryString() : ""));

        return $this->mobileAppRedirector->createRedirect($redirectUrl, $request);
    }
}
