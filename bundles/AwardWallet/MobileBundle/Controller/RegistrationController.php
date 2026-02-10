<?php

namespace AwardWallet\MobileBundle\Controller;

use AwardWallet\MainBundle\Configuration\AwSecureToken;
use AwardWallet\MainBundle\Configuration\JsonDecode;
use AwardWallet\MainBundle\Controller\HomeController;
use AwardWallet\MainBundle\Entity\Invites;
use AwardWallet\MainBundle\Entity\Useragent;
use AwardWallet\MainBundle\Entity\Usr;
use AwardWallet\MainBundle\FrameworkExtension\ControllerTrait;
use AwardWallet\MainBundle\FrameworkExtension\Error\SafeExecutorFactory;
use AwardWallet\MainBundle\FrameworkExtension\JsonTrait;
use AwardWallet\MainBundle\FrameworkExtension\Listeners\ExternalTracking\ExternalTrackingListHandler;
use AwardWallet\MainBundle\FrameworkExtension\Listeners\ExternalTracking\Superfly;
use AwardWallet\MainBundle\FrameworkExtension\Listeners\ReferalListener;
use AwardWallet\MainBundle\Globals\ApiVersioning\ApiVersioningService;
use AwardWallet\MainBundle\Globals\ApiVersioning\MobileVersions;
use AwardWallet\MainBundle\Globals\FormDehydrator;
use AwardWallet\MainBundle\Globals\GlobalVariables;
use AwardWallet\MainBundle\Globals\Localizer\LocalizeService;
use AwardWallet\MainBundle\Manager\UserManager;
use AwardWallet\MainBundle\Security\Authenticator\Step\Recaptcha\CaptchaStepHelper;
use AwardWallet\MainBundle\Security\Captcha\Resolver\MobileCaptchaResolver;
use AwardWallet\MainBundle\Security\Reauthentication\Mobile\MobileReauthenticationRequestListener;
use AwardWallet\MainBundle\Service\GoogleAnalytics4;
use AwardWallet\MobileBundle\Form\Model\RegisterModel;
use AwardWallet\MobileBundle\Form\Type\NewDesign\RegisterNewUserType;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Form\FormError;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

class RegistrationController extends AbstractController
{
    use ControllerTrait;
    use JsonTrait;

    private const RECAPTCHA_RESPONSE_FIELD = 'recaptcha_response';

    public function __construct(
        LocalizeService $localizeService
    ) {
        $localizeService->setRegionalSettings();
    }

    /**
     * @Route("/register", name="awm_newapp_register", methods={"GET", "POST"})
     * @JsonDecode
     * @AwSecureToken(
     *     service="aw.mobile_token_checker",
     *     lifetime=60,
     *     triggerFeatures={
     *         MobileVersions::NATIVE_APP,
     *     },
     *     methods={"POST"}
     * )
     * @return JsonResponse
     */
    public function registerAction(
        Request $request,
        MobileCaptchaResolver $captchaResolver,
        AuthorizationCheckerInterface $authorizationChecker,
        GlobalVariables $globalVariables,
        ApiVersioningService $apiVersioning,
        TranslatorInterface $translator,
        UserManager $userManager,
        SafeExecutorFactory $safeExecFactory,
        EntityManagerInterface $entityManager,
        ExternalTrackingListHandler $externalTrackingListHandler,
        FormDehydrator $formDehydrator,
        GoogleAnalytics4 $googleAnalytics4
    ) {
        if ($this->isAuthorized($authorizationChecker) || $globalVariables->isSiteModeBusiness()) {
            return $this->errorJsonResponse('Already authenticated', [
                'authenticated' => true,
            ]);
        }
        // Form
        $usr = new Usr();
        $registerModel = new RegisterModel();

        if ($apiVersioning->supports(MobileVersions::TERMS_OF_USE_ON_REGISTER)) {
            $registerModel->setAgree(true);
        }

        $registerModel->setUser($usr);

        if ($invite = $this->getInvite($request, $entityManager)) {
            $usr->setEmail($invite->getEmail());
        }

        $isNative = $apiVersioning->supports(MobileVersions::NATIVE_APP);
        $captchaProvider = $captchaResolver->resolve($request);
        $form = $this->createForm(RegisterNewUserType::class, $registerModel, [
            'recaptcha_override' => $request->headers->has(CaptchaStepHelper::WANT_RECAPTCHA_HEADER),
            'captcha_provider' => $captchaProvider,
        ]);
        $request->request->replace([$form->getName() => $request->request->all()]);

        if ('POST' === $request->getMethod()) {
            $recaptchaResponse = null;
            $formName = $form->getName();

            if (
                $request->request->has($formName)
                && \is_array($request->request->get($formName))
                && isset($request->request->get($formName)[self::RECAPTCHA_RESPONSE_FIELD])
                && \is_string($request->request->get($formName)[self::RECAPTCHA_RESPONSE_FIELD])
            ) {
                $recaptchaResponse = $request->request->get($formName)[self::RECAPTCHA_RESPONSE_FIELD];
            }

            if (
                $form->has('recaptcha')
                && !$captchaProvider->getValidator()->validate($recaptchaResponse ?? '', $request->getClientIp() ?? '')
            ) {
                $form->addError(new FormError($translator->trans("invalid_captcha", [], "validators")));
            } else {
                $requestData = $request->request->all();
                unset($requestData[$formName][self::RECAPTCHA_RESPONSE_FIELD]);
                $request->request->replace($requestData);
                $form->handleRequest($request);
                $this->checkCsrfToken($authorizationChecker);

                if ($form->isSubmitted() && $form->isValid()) {
                    $session = $request->getSession();
                    $ref = $session->get(ReferalListener::SESSION_REF_KEY);
                    $usr
                        ->setRegistrationPlatform(
                            $isNative
                                ? Usr::REGISTRATION_PLATFORM_MOBILE_APP
                                : Usr::REGISTRATION_PLATFORM_MOBILE_BROWSER
                        )
                        ->setRegistrationMethod(Usr::REGISTRATION_METHOD_FORM);
                    $userManager->registerUser($usr, $request);
                    // Auth
                    $userManager->loadToken($usr, true);

                    $safeExecFactory(fn () => $this->sendGoogleAnalytics($usr->getId(), $googleAnalytics4))();

                    if ($apiVersioning->supports(MobileVersions::KEYCHAIN_REAUTH)) {
                        $session->set(MobileReauthenticationRequestListener::SESSION_ENABLE_KEYCHAIN_AFTER_LOGGING_IN_KEY, true);
                    }

                    $responseData = ['userId' => $usr->getId()];

                    if (
                        $invite
                        /** @var Useragent $userAgent */
                        && $userAgent = $entityManager->getRepository(Useragent::class)->findOneBy([
                            'clientid' => $usr,
                            'agentid' => $invite->getInviterid(),
                            'isapproved' => true,
                        ])
                    ) {
                        $responseData['connection'] = $userAgent->getUseragentid();
                    }

                    $response = $this->jsonResponse($responseData);

                    if (HomeController::SUPERFLY_LEAD_ID == $ref) {
                        $externalTrackingListHandler->add(new Superfly([Superfly::EVENT_COMPLETE_REGISTRATION]));
                    }

                    return $response;
                }
            }
        }

        return new JsonResponse($formDehydrator->dehydrateForm($form, false));
    }

    protected function getInvite(Request $request, EntityManagerInterface $entityManager): ?Invites
    {
        $invite = null;
        $invId = $request->getSession()->get('invId', null);
        $inviteCode = $request->getSession()->get('InviteCode', null);

        $invitesRepsotory = $entityManager->getRepository(Invites::class);

        if ($invId) {
            $invite = $invitesRepsotory->find($invId);
        } elseif ($inviteCode) {
            $invite = $invitesRepsotory->findOneBy(['code' => $inviteCode]);
        }

        if ($invite && $invite->getApproved() == 0) {
            return $invite;
        }

        return null;
    }

    private function sendGoogleAnalytics(
        int $userId,
        GoogleAnalytics4 $googleAnalytics4
    ): void {
        // TODO: GA4 redo check events
        /*
        $googleAnalytics
            ->setEventCategory('user')
            ->setEventAction('registered')
            ->setEventLabel('mobile')
            ->setEventValue(1)
            ->setDataSource('desktop')
            ->sendEvent();
        */

        //        $apiVersioning = $this->get('aw.api.versioning');
        $googleAnalytics4->sendEvents(
            GoogleAnalytics4::PLATFORM_WEB,
            $userId,
            [[
                'name' => 'registered',
                'params' => [
                    'category' => 'user',
                    'label' => 'mobile',
                ],
            ]]
        );
    }
}
