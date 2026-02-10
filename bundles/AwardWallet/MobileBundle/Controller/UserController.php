<?php

namespace AwardWallet\MobileBundle\Controller;

use AwardWallet\MainBundle\Configuration\JsonDecode;
use AwardWallet\MainBundle\Entity\Repositories\UsrRepository;
use AwardWallet\MainBundle\Entity\Usr;
use AwardWallet\MainBundle\Form\Extension\JsonFormExtension\JsonRequestHandler;
use AwardWallet\MainBundle\Form\Type\UserPasswordType;
use AwardWallet\MainBundle\FrameworkExtension\AwTokenStorageInterface;
use AwardWallet\MainBundle\FrameworkExtension\ControllerTrait;
use AwardWallet\MainBundle\FrameworkExtension\JsonTrait;
use AwardWallet\MainBundle\FrameworkExtension\Listeners\ReferalListener;
use AwardWallet\MainBundle\FrameworkExtension\Mailer\Tracker\BrowserContextFactory;
use AwardWallet\MainBundle\FrameworkExtension\Mailer\Tracker\ClickTracker;
use AwardWallet\MainBundle\Globals\ApiVersioning\ApiVersioningService;
use AwardWallet\MainBundle\Globals\ApiVersioning\MobileVersions;
use AwardWallet\MainBundle\Globals\ClientVerificationHandler;
use AwardWallet\MainBundle\Globals\Headers\MobileHeaders;
use AwardWallet\MainBundle\Globals\Localizer\LocalizeService;
use AwardWallet\MainBundle\Globals\StringUtils;
use AwardWallet\MainBundle\Manager\SiteAdManager;
use AwardWallet\MainBundle\Manager\UserManager;
use AwardWallet\MainBundle\Parameter\UnremovableUsersParameter;
use AwardWallet\MainBundle\Security\AntiBruteforceLockerService;
use AwardWallet\MainBundle\Security\Captcha\Provider\CloudflareTurnstileCaptchaProvider;
use AwardWallet\MainBundle\Security\Captcha\Provider\GoogleRecaptchaCaptchaProvider;
use AwardWallet\MainBundle\Security\Captcha\Resolver\MobileCaptchaResolver;
use AwardWallet\MainBundle\Security\Reauthentication\Mobile\MobileReauthenticatorHandler;
use AwardWallet\MainBundle\Service\PageVisitLogger;
use AwardWallet\MainBundle\Service\UserRemover;
use Psr\Log\LoggerInterface;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Security;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\FormError;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;
use Symfony\Component\Security\Core\Exception\AuthenticationException;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Contracts\Translation\TranslatorInterface;

use function AwardWallet\MainBundle\Globals\Utils\f\call;
use function AwardWallet\MainBundle\Globals\Utils\iterFluent\it;

/*
 * Support /m/api/.* calls
 */
class UserController extends AbstractController
{
    use ControllerTrait;
    use JsonTrait;

    private PageVisitLogger $pageVisitLogger;
    private LoggerInterface $logger;

    public function __construct(
        LocalizeService $localizeService,
        PageVisitLogger $pageVisitLogger, LoggerInterface $logger
    ) {
        $localizeService->setRegionalSettings();
        $this->pageVisitLogger = $pageVisitLogger;
        $this->logger = $logger;
    }

    /**
     * @Route("/login_status", name="awm_new_login_status", methods={"GET"})
     */
    public function statusAction(AuthorizationCheckerInterface $authorizationChecker, ClientVerificationHandler $clientVerificationHandler)
    {
        $response = new JsonResponse(['authorized' => $this->isAuthorized($authorizationChecker)]);
        $response->headers->set(
            'X-Scripted',
            $clientVerificationHandler->getClientCheck()['jsExpression']
        );

        return $response;
    }

    /**
     * @Route("/login_check", name="awm_new_login_check", methods={"POST"})
     * @Security("is_granted('CSRF')")
     */
    public function loginCheckAction(Request $request)
    {
        throw new \LogicException('unreachable');
    }

    /**
     * @Route("/logout", name="awm_new_logout", methods={"GET"})
     * @Security("is_granted('CSRF')")
     */
    public function logoutAction(Request $request)
    {
        if (strpos($request->getPathInfo(), '/user/delete') === false) {
            $this->pageVisitLogger->log(PageVisitLogger::PAGE_LOGOUT, true);
        }

        return $this->forward('AwardWallet\MainBundle\Controller\LogoutController::logoutAction');
    }

    /**
     * @Route("/impersonate", methods={"POST"})
     * @Security("is_granted('ROLE_MANAGE_IMPERSONATE') and is_granted('NOT_USER_IMPERSONATED')")
     */
    public function impersonateAction(Request $request, UserManager $um)
    {
        $form = JsonRequestHandler::parse($request);

        if (!(isset($form['loginOrEmail']) && '' !== $form['loginOrEmail'])) {
            throw $this->createNotFoundException('User not found');
        }

        $fullImpersonate = isset($form['fullImpersonate']) ? (bool) $form['fullImpersonate'] : false;

        if (
            $fullImpersonate
            && !$this->isGranted('FULL_IMPERSONATE')
        ) {
            throw $this->createNotFoundException('You are not allowed to use full impersonate');
        }

        try {
            $user = $um->findUser($form["loginOrEmail"], false);
            /** @var Usr $user */
            $um->impersonate($user, $fullImpersonate, false, "/");

            return new JsonResponse(
                [
                    'success' => true,
                    'userId' => $user->getUserid(),
                ]
            );
        } catch (AuthenticationException $e) {
            throw $this->createNotFoundException($e->getMessage());
        }
    }

    /**
     * @Route("/recaptcha", methods={"GET"})
     * @Template("@AwardWalletMobile/User/recaptcha.html.twig")
     */
    public function recaptchaAction(Request $request, MobileCaptchaResolver $captchaResolver, GoogleRecaptchaCaptchaProvider $googleRecaptchaNotARobotV2CaptchaProvider): array
    {
        return [
            'captcha_provider' => $request->headers->has(MobileHeaders::MOBILE_PLATFORM)
                ? $captchaResolver->resolve($request)
                : $googleRecaptchaNotARobotV2CaptchaProvider,
        ];
    }

    /**
     * @Route("/recaptcha-turnstile", methods={"GET"})
     * @Template("@AwardWalletMobile/User/recaptcha.html.twig")
     */
    public function recaptchaTurnstileAction(CloudflareTurnstileCaptchaProvider $cloudflareTurnstileCaptchaProvider): array
    {
        return [
            'captcha_provider' => $cloudflareTurnstileCaptchaProvider,
        ];
    }

    /**
     * @Route("/ref", methods={"POST"})
     * @Security("is_granted('CSRF')")
     * @JsonDecode()
     */
    public function saveRefAction(Request $request, LoggerInterface $loggerStat, SiteAdManager $siteAdManager): Response
    {
        $ref = $request->get('ref');
        $firstAppOpen = $request->get('first_app_open', false);
        $trackClick = $request->get('track_click', 0);
        $advertiserId = $request->get('advertiser_id');

        if (!(\is_string($ref) || \is_int($ref)) || empty($ref)) {
            throw new BadRequestHttpException();
        }

        $ref = (int) $ref;

        if (\is_bool($firstAppOpen) && $firstAppOpen) {
            $siteAdManager->updateAppInstallsForRef($ref);
        }

        if ((\is_int($trackClick) || \is_bool($trackClick)) && $trackClick) {
            $siteAdManager->updateClicksForRef($ref);
        }

        $loggerStat->info('click_tracking', [
            'ref' => $ref,
            'first_app_open' => $firstAppOpen,
            'track_click' => $trackClick,
            'advertiser_id' => $advertiserId,
        ]);
        $session = $request->getSession();

        if ($session) {
            $session->set(ReferalListener::SESSION_REF_KEY, $ref);

            if (\is_scalar($advertiserId)) {
                $session->set(ReferalListener::SESSION_ADVERTISER_ID_KEY, $advertiserId);
            }
        }

        return $this->successJsonResponse();
    }

    /**
     * @Route("/track", methods={"POST"})
     * @Security("is_granted('CSRF')")
     * @JsonDecode()
     */
    public function trackingIdAction(Request $request, ClickTracker $clickTracker): Response
    {
        $trackingId = $request->get('id');
        $firstAppOpen = (bool) $request->get('first_app_open', false);
        $url = $request->get('url');

        if (!\is_string($trackingId) || StringUtils::isEmpty($url)) {
            throw new BadRequestHttpException();
        }

        if (!\is_string($url) || StringUtils::isEmpty($url)) {
            throw new BadRequestHttpException();
        }

        $request->attributes->set(BrowserContextFactory::MOBILE_APP_FRESH_INSTALL_REQUEST_ATTRIBUTE, $firstAppOpen);
        $clickTracker->trackClickUnencodedId($trackingId, $url, $request);

        return $this->successJsonResponse();
    }

    /**
     * @Route("/external-tracking/{base64Data}", methods={"GET"})
     */
    public function externalTrack(string $base64Data): Response
    {
        $rawData = @\base64_decode($base64Data);

        if (!\is_string($rawData)) {
            throw $this->createNotFoundException();
        }

        $providers = @\json_decode($rawData);

        if (!\is_array($providers)) {
            throw $this->createNotFoundException();
        }

        $content = $this->render('@AwardWalletMain/Mobile/ExternalTracking/externalTracking.html.twig', [
            'providers' => $providers,
        ]);

        return new Response($content);
    }

    /**
     * @Route("/user/delete", methods={"POST"})
     * @Security("is_granted('CSRF')")
     * @JsonDecode
     */
    public function deleteAction(
        Request $request,
        TranslatorInterface $translator,
        UsrRepository $usrRepository,
        ApiVersioningService $apiVersioning,
        MobileReauthenticatorHandler $mobileReauthenticatorHandler,
        UserRemover $userRemover,
        AwTokenStorageInterface $awTokenStorage,
        AuthorizationCheckerInterface $authorizationChecker,
        AntiBruteforceLockerService $awSecurityAntibruteforceLogin,
        UnremovableUsersParameter $unremovableUsersParameter
    ) {
        $user = $awTokenStorage->getBusinessUser();
        $issetBusinessAccount = $usrRepository->isUserBusinessAdmin($user);

        if ($issetBusinessAccount) {
            return $this->jsonResponse([
                'success' => false,
                'unlinkFromBusiness' => true,
                'companyName' => \htmlspecialchars($usrRepository->getBusinessByUser($user)->getCompany()),
            ]);
        }

        /** @var Usr $user */
        $user = $this->getUser();
        $loginError = $awSecurityAntibruteforceLogin->checkForLockout($user->getLogin());

        if (StringUtils::isNotEmpty($loginError)) {
            return $this->jsonResponse([
                'success' => false,
                'error' => $loginError,
            ]);
        }

        $unremovableUsers = $unremovableUsersParameter->get();

        if (\in_array($user->getUserid(), $unremovableUsers)) {
            return $this->jsonResponse([
                'success' => false,
                'error' => $translator->trans('account_not_be_deleted', [], 'validators'),
            ]);
        }

        $supportsOauth = $apiVersioning->supports(MobileVersions::LOGIN_OAUTH);
        $formBuilder = $this->createFormBuilder(null, ['csrf_protection' => false]);

        if (!$supportsOauth) {
            $formBuilder->add('password', UserPasswordType::class, [
                'allow_master_password' => true,
                'user_login' => $user->getLogin(),
                'user_ip' => $request->getClientIp(),
            ]);
        }

        $formBuilder->add('reason', TextareaType::class, [
            'allow_quotes' => true,
            'label' => $translator->trans('user.delete.reason'),
            'constraints' => [
                new Assert\NotBlank(),
            ],
        ]);
        $form = $formBuilder->getForm();
        $request->request->replace([
            $form->getName() => \array_merge(
                ['reason' => $request->request->get('reason', '')],
                !$supportsOauth ? ['password' => $request->request->get('password', '')] : []
            ),
        ]);

        if ($supportsOauth) {
            $reauthResponse = $mobileReauthenticatorHandler->handleAuto($request);

            if ($reauthResponse) {
                return $reauthResponse;
            }
        }

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $this->checkImpersonation($authorizationChecker);
            $userRemover->deleteUser(
                $user,
                $form->get('reason')->getData()
            );

            return $this->logoutAction($request);
        } else {
            return $this->jsonResponse(it(call(function () use ($form) {
                yield 'success' => false;

                /** @var FormError[] $formErrors */
                if ($formErrors = it($form->getErrors())->toArray()) {
                    yield 'error' => $formErrors[0]->getMessage();
                }

                /** @var FormError[] $formErrors */
                if (
                    $form->has('password')
                    && $fieldErrors = it($form->get('password')->getErrors())->toArray()
                ) {
                    yield 'passwordError' => $fieldErrors[0]->getMessage();
                }

                /** @var FormError[] $formErrors */
                if ($fieldErrors = it($form->get('reason')->getErrors())->toArray()) {
                    yield 'reasonError' => $fieldErrors[0]->getMessage();
                }
            }))->toArrayWithKeys());
        }
    }
}
