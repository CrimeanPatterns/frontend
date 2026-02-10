<?php

namespace AwardWallet\MainBundle\Security\Authenticator\EntryPoint;

use AwardWallet\MainBundle\Form\Extension\JsonFormExtension\JsonRequestHandler;
use AwardWallet\MainBundle\Globals\ApiVersioning\ApiVersioningService;
use AwardWallet\MainBundle\Globals\ApiVersioning\MobileVersions;
use AwardWallet\MainBundle\Globals\StringUtils;
use AwardWallet\MainBundle\Security\Authenticator\Credentials;
use AwardWallet\MainBundle\Security\Authenticator\Step\CallableStep;
use AwardWallet\MainBundle\Security\Authenticator\Step\Exception\AbstractStepAuthenticationException;
use AwardWallet\MainBundle\Security\Authenticator\Step\IpLockoutStep;
use AwardWallet\MainBundle\Security\Authenticator\Step\LoadUserStep;
use AwardWallet\MainBundle\Security\Authenticator\Step\LoginPasswordLockoutStep;
use AwardWallet\MainBundle\Security\Authenticator\Step\LoginPasswordStep;
use AwardWallet\MainBundle\Security\Authenticator\Step\OneTimeCodeByAppStep;
use AwardWallet\MainBundle\Security\Authenticator\Step\OneTimeCodeByEmail\OneTimeCodeByEmailStep;
use AwardWallet\MainBundle\Security\Authenticator\Step\Recaptcha\MobileCaptchaStep;
use AwardWallet\MainBundle\Security\Authenticator\Step\ScriptedStep;
use AwardWallet\MainBundle\Security\Authenticator\Step\StepData;
use Psr\Log\LoggerInterface;
use Psr\Log\LogLevel;
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

class MobileOldFormEntryPoint extends AbstractLoginEntryPoint
{
    /**
     * @var ApiVersioningService
     */
    protected $apiVersioning;
    /**
     * @var FormEntryPointHelper
     */
    protected $formEntryPointHelper;
    /**
     * @var TranslatorInterface
     */
    protected $translator;
    /**
     * @var LoggerInterface
     */
    protected $logger;
    /**
     * @var AuthKeyChecker
     */
    private $authKeyChecker;

    public function __construct(
        iterable $steps,
        iterable $stepFactories,
        ApiVersioningService $apiVersioning,
        FormEntryPointHelper $formEntryPointHelper,
        TranslatorInterface $translator,
        AuthKeyChecker $authKeyChecker,
        LoggerInterface $logger
    ) {
        $this->apiVersioning = $apiVersioning;
        $this->steps = $steps;
        $this->formEntryPointHelper = $formEntryPointHelper;
        $this->translator = $translator;
        $this->logger = $logger;
        $this->stepFactories = $stepFactories;
        $this->authKeyChecker = $authKeyChecker;
    }

    /**
     * @return JsonResponse|Response
     */
    public function start(Request $request, ?AuthenticationException $authException = null): Response
    {
        return new JsonResponse(['error' => $this->translator->trans('access.denied')], 403);
    }

    public function getCredentials(Request $request): Credentials
    {
        $stepData = new StepData();

        if ($json = JsonRequestHandler::parse($request)) {
            $request->request->replace($json);
        }

        $request->request->set('_remember_me', '1');

        $stepData
            ->setLogin($request->get('login'))
            ->setPassword($request->get('password'))
            ->setOtcAppCode($otcCode = trim(substr($request->get('_otc'), 0, 1000), "\r\n\t *"))
            ->setOtcEmailCode($otcCode)
            ->setScripted($request->headers->get('X-Scripted'))
            ->setRecaptcha(StringUtils::isNotEmpty($recaptcha = $request->get('recaptcha')) ? $recaptcha : null);

        return new Credentials($stepData, $request);
    }

    public function supportsArea(Request $request): bool
    {
        return
            (new RequestMatcher('^/m/api/'))->matches($request)
            && $this->apiVersioning->notSupports(MobileVersions::DETAILED_LOGIN_CHECK_RESPONSE);
    }

    public function getId(): string
    {
        return 'mobile_old';
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

        if ($this->apiVersioning->supports(MobileVersions::SCRIPTED_BOT_PROTECTION)) {
            $this->getStep(ScriptedStep::ID)->check($credentials);
        }

        $this->getStep(LoginPasswordStep::ID)->check($credentials);

        $secondFactorStepChain = $this
            ->getStep(OneTimeCodeByAppStep::ID)
            ->otherwise(new CallableStep(function (Credentials $credentials): bool {
                if ($this->authKeyChecker->keyExists($credentials)) {
                    return false;
                }

                return $this->getStep(OneTimeCodeByEmailStep::ID)->check($credentials);
            }));

        $secondFactorStepChain->check($credentials);

        return true;
    }

    public function onAuthenticationFailure(Request $request, AuthenticationException $exception): ?Response
    {
        parent::onAuthenticationFailure($request, $exception);

        $data = [
            'success' => false,
        ];

        if ($exception instanceof AbstractStepAuthenticationException) {
            $stepId = $exception->getStep()->getId();

            $data['message'] = $this->translator->trans(/** @Ignore */ $exception->getMessage(), [], 'validators');

            switch ($stepId) {
                case LoginPasswordLockoutStep::ID:
                case IpLockoutStep::ID:
                case ScriptedStep::ID:
                case LoginPasswordStep::ID:
                    $data['otcRequired'] = false;
                    $data['badCredentials'] = true;

                    break;

                case OneTimeCodeByAppStep::ID:
                    $data['otcInputLabel'] = $this->translator->trans("login.otc");
                    $data['otcInputHint'] = $this->translator->trans("login.bottom.otc-recovery.hint");
                    $data['otcRequired'] = true;
                    $data['otcShowRecovery'] = true;

                    break;

                case OneTimeCodeByEmailStep::ID:
                    $data['otcInputLabel'] = $this->translator->trans("login.otc.label");
                    $data['otcRequired'] = true;
                    $data['otcShowRecovery'] = true;

                    break;

                case MobileCaptchaStep::ID:
                    $data['recaptchaRequired'] = true;

                    break;
            }
        } elseif ($exception instanceof UsernameNotFoundException) {
            $data['message'] = $this->translator->trans('Bad credentials', [], 'validators');
            $data['badCredentials'] = true;
        } else {
            $data['message'] = $this->translator->trans(/** @Ignore */ $exception->getMessage(), [], 'validators');
        }

        return new JsonResponse($data);
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
        return $this->matchRequestByRoute($request, 'awm_new_login_check');
    }

    protected function log(string $message, array $context = [], $level = LogLevel::WARNING): void
    {
        $this->logger->log($level, $message, $context);
    }
}
