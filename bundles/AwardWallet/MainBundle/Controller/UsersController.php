<?php

namespace AwardWallet\MainBundle\Controller;

use AwardWallet\MainBundle\Configuration\JsonDecode;
use AwardWallet\MainBundle\Entity\BusinessInfo;
use AwardWallet\MainBundle\Entity\Cart;
use AwardWallet\MainBundle\Entity\Repositories\CartRepository;
use AwardWallet\MainBundle\Entity\Repositories\UseragentRepository;
use AwardWallet\MainBundle\Entity\Repositories\UsrRepository;
use AwardWallet\MainBundle\Entity\Useragent;
use AwardWallet\MainBundle\Entity\Usr;
use AwardWallet\MainBundle\Form\Type\UserType;
use AwardWallet\MainBundle\FrameworkExtension\AwTokenStorageInterface;
use AwardWallet\MainBundle\FrameworkExtension\FormErrorHandler;
use AwardWallet\MainBundle\Globals\ClientVerificationHandler;
use AwardWallet\MainBundle\Globals\Image\AvatarCreator;
use AwardWallet\MainBundle\Manager\UserManager;
use AwardWallet\MainBundle\Parameter\UnremovableUsersParameter;
use AwardWallet\MainBundle\Security\AntiBruteforceLockerService;
use AwardWallet\MainBundle\Security\Captcha\Resolver\DesktopCaptchaResolver;
use AwardWallet\MainBundle\Security\LoginRedirector;
use AwardWallet\MainBundle\Security\Reauthentication\Action;
use AwardWallet\MainBundle\Security\Reauthentication\Mobile\MobileReauthenticatorHandler;
use AwardWallet\MainBundle\Security\RememberMe\RememberMeTokenProvider;
use AwardWallet\MainBundle\Security\SessionListener;
use AwardWallet\MainBundle\Security\Utils;
use AwardWallet\MainBundle\Service\UserRemover;
use AwardWallet\WidgetBundle\Widget\UserProfileWidget;
use Doctrine\ORM\EntityManagerInterface;
use JMS\TranslationBundle\Annotation\Desc;
use Psr\Log\LoggerInterface;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Security;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Form\Extension\Core\Type\FileType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormError;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Session\SessionInterface;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Routing\RouterInterface;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Component\Validator\Context\ExecutionContextInterface;
use Symfony\Contracts\Translation\TranslatorInterface;
use Twig\Environment;

/**
 * Class UsersController.
 *
 * @Route("/")
 */
class UsersController extends AbstractController
{
    public const SESSION_LOGIN_USERNAME = 'suggested-login-username';
    private LoggerInterface $logger;
    private RouterInterface $router;
    private AuthorizationCheckerInterface $authorizationChecker;
    private AntiBruteforceLockerService $securityAntibruteforceForgot;
    private AntiBruteforceLockerService $securityAntibruteforceCheckEmail;
    private TranslatorInterface $translator;
    private AwTokenStorageInterface $tokenStorage;
    private UsrRepository $usrRepository;
    private EntityManagerInterface $entityManager;
    private UserManager $userManager;
    private UserProfileWidget $userProfileWidget;

    public function __construct(
        LoggerInterface $logger,
        RouterInterface $router,
        AuthorizationCheckerInterface $authorizationChecker,
        AntiBruteforceLockerService $securityAntibruteforceForgot,
        AntiBruteforceLockerService $securityAntibruteforceCheckEmail,
        TranslatorInterface $translator,
        AwTokenStorageInterface $tokenStorage,
        UsrRepository $usrRepository,
        EntityManagerInterface $entityManager,
        UserManager $userManager,
        UserProfileWidget $userProfileWidget
    ) {
        $this->logger = $logger;
        $this->router = $router;
        $this->authorizationChecker = $authorizationChecker;
        $this->securityAntibruteforceForgot = $securityAntibruteforceForgot;
        $this->securityAntibruteforceCheckEmail = $securityAntibruteforceCheckEmail;
        $this->translator = $translator;
        $this->tokenStorage = $tokenStorage;
        $this->usrRepository = $usrRepository;
        $this->entityManager = $entityManager;
        $this->userManager = $userManager;
        $this->userProfileWidget = $userProfileWidget;
    }

    /**
     * @Route("/restore/{username}", name="aw_users_restore_plain", options={"expose"=true})
     * @Template("@AwardWalletMain/Users/restorePlain.html.twig")
     */
    public function restorePlainAction(Request $request, $username, SessionInterface $session, ClientVerificationHandler $clientVerification)
    {
        if (empty($username)) {
            return $this->redirect($this->router->generate("aw_restore"));
        }

        if ($this->authorizationChecker->isGranted('ROLE_USER')) {
            return new RedirectResponse($this->router->generate('aw_users_logout', ['BackTo' => $request->getRequestUri()]));
        }

        $error = $this->securityAntibruteforceForgot->checkForLockout($request->getClientIp());

        if (!empty($error)) {
            return $this->redirect($this->router->generate("aw_restore"));
        }

        $username = base64_decode($username);

        if (!preg_match('#^[a-z_0-9A-Z\-]+$#ims', $username)) {
            return $this->redirect($this->router->generate("aw_restore"));
        }
        $user = $this->usrRepository->loadUserByUsername($username, false);

        if (!$user) {
            return $this->redirect($this->router->generate("aw_restore"));
        }

        if ($session->has("client_check")) {
            $clientCheck = $session->get("client_check");
        } else {
            $clientCheck = $clientVerification->getClientCheck();
        }

        if ($request->isMethod('POST')) {
            if ($clientCheck['result'] != $request->request->get('exp')) {
                return $this->redirect($this->router->generate("aw_restore"));
            }

            $result = $this->restorePasswordRequest($request, $username);

            return [
                'confirmed' => true,
                'success' => $result === true,
                'error' => $result,
            ];
        } else {
            return [
                'confirmed' => false,
                'exp' => $clientCheck['jsExpression'],
            ];
        }
    }

    /**
     * @Route("/user/restore", name="aw_users_restore", options={"expose"=true})
     * c
     * @Template("@AwardWalletMain/Users/restore.html.twig")
     */
    public function restoreAction(Request $request)
    {
        $result = $this->restorePasswordRequest($request);

        return new JsonResponse([
            'success' => $result === true,
            'error' => $result,
        ]);
    }

    /**
     * @Route("/user/register", name="aw_users_register", methods={"POST"}, options={"expose"=true})
     * @JsonDecode
     * @Security("is_granted('NOT_SITE_BUSINESS_AREA') and is_granted('CSRF')")
     */
    public function registerAction(
        Request $request,
        LoginRedirector $loginRedirector,
        LoggerInterface $securityLogger,
        DesktopCaptchaResolver $captchaResolver,
        SessionInterface $session
    ) {
        $logger = $securityLogger;
        $form = $this->createForm(UserType::class, new Usr(), [
            'csrf_protection' => false,
        ]);
        $form->handleRequest($request);
        /** @var Usr $user */
        $user = $form->getData();
        $targetPage = null;
        $captchaProvider = $captchaResolver->resolve($request);

        if (
            // was already checked in unique email action?
            !$session->has(self::getCaptchaSessionKey($request->request->get("recaptcha") ?? ''))
            && !$captchaProvider->getValidator()->validate($request->request->get("recaptcha") ?? '', $request->getClientIp() ?? '')
        ) {
            $form->addError(new FormError($this->translator->trans(/** @Desc("Invalid security code, please try again") */ "invalid_captcha", [], "validators")));
        }

        $success = false;
        $beta = false;
        $userId = null;

        if ($form->isSubmitted() && $form->isValid()) {
            // apply coupon
            $logger->info("form is valid");
            $coupon = (string) $request->request->get('coupon');
            $user
                ->setRegistrationPlatform(Usr::REGISTRATION_PLATFORM_DESKTOP_BROWSER)
                ->setRegistrationMethod(Usr::REGISTRATION_METHOD_FORM);
            $this->userManager->registerUser($user, $request);
            $this->userManager->loadToken($user, true);

            if (!empty($coupon)) {
                $logger->info("applied coupon: {$coupon}");
                $request->getSession()->set('coupon', $coupon);
            }
            $success = true;

            $targetPage = $loginRedirector->getRegistrationTargetPage($user, $request->query->all());

            if ($user->getInbeta() && $user->getBetaapproved()) {
                $beta = true;
            }

            $userId = $user->getUserid();
        }

        $templateParams = [
            'success' => $success,
            'errors' => (string) $form->getErrors(true, false),
            'targetPage' => $targetPage,
            'beta' => $beta,
            'userId' => $userId,
        ];
        $logger->info("register result: " . json_encode($templateParams));

        return new JsonResponse($templateParams);
    }

    /**
     * @Route("/user/register_business", name="aw_users_register_business", methods={"POST"}, options={"expose"=true})
     * @JsonDecode
     * @Security("is_granted('SITE_BUSINESS_AREA') and is_granted('CSRF')")
     */
    public function registerBusinessAction(
        Request $request,
        LoginRedirector $loginRedirector,
        DesktopCaptchaResolver $captchaResolver,
        FormErrorHandler $formErrorHandler
    ) {
        $user = (new Usr())->setAccountlevel(ACCOUNT_LEVEL_BUSINESS);
        $form = $this->createForm(UserType::class, $user);

        $error = $this->securityAntibruteforceForgot->checkForLockout($request->getClientIp());

        if (!$error) {
            $form->handleRequest($request);
            $captchaProvider = $captchaResolver->resolve($request);

            if (!$captchaProvider->getValidator()->validate($request->request->get("recaptcha") ?? '', $request->getClientIp() ?? '')) {
                $form->addError(new FormError($this->translator->trans("invalid_captcha", [], "validators")));
            }

            if ($form->isSubmitted() && $form->isValid()) {
                /** @var Usr $personalUser */
                $personalUser = clone $user;
                $personalUser->setAccountlevel(ACCOUNT_LEVEL_FREE);
                $personalUser->setPicturever(null);
                $this->userManager->registerUser($personalUser, $request);

                /** @var Usr $businessUser */
                $businessUser = clone $user;
                $businessUser->clearGroups();
                $this->userManager->registerUser($businessUser, $request, false, $personalUser->getId(), false); // UserId используется как флаг регистрации бизнеса и служит для генерации Business Email

                $ua = new Useragent();
                $ua->setClientid($businessUser);
                $ua->setAgentid($personalUser);
                $ua->setAccesslevel(ACCESS_ADMIN);
                $ua->setIsapproved(1);
                $ua->setSharebydefault(0);
                $ua->setTripsharebydefault(1);
                $ua->setSharecode(RandomStr(ord('a'), ord('z'), 10));
                $this->entityManager->persist($ua);

                $ua = new Useragent();
                $ua->setClientid($personalUser);
                $ua->setAgentid($businessUser);
                $ua->setAccesslevel(ACCESS_WRITE);
                $ua->setIsapproved(1);
                $ua->setSharebydefault(0);
                $ua->setTripsharebydefault(0);
                $ua->setSharecode(RandomStr(ord('a'), ord('z'), 10));
                $this->entityManager->persist($ua);

                $this->entityManager->flush();

                $this->userManager->loadToken($personalUser, true);
                $redirectTo = $loginRedirector->getRegistrationTargetPage($personalUser, $request->query->all());
            }
            $errors = $formErrorHandler->getFormErrors($form, true, false);
        } else {
            $errors = [
                [
                    'errorText' => $error,
                    'name' => 'registration[user][login]',
                ],
            ];
        }

        return new JsonResponse([
            'errors' => $errors,
            'redirectTo' => $redirectTo ?? '/',
        ]);
    }

    public function getBusinessFormAction()
    {
        $user = (new Usr())->setAccountlevel(ACCOUNT_LEVEL_BUSINESS);
        $form = $this->createForm(UserType::class, $user);

        return $this->render(
            '@AwardWalletMain/Users/businessRegister.html.twig',
            ['form' => $form->createView()]
        );
    }

    /**
     * @Route("/user/check_login", name="aw_users_check_login", methods={"POST"}, options={"expose"=true})
     * @JsonDecode
     * @Security("is_granted('CSRF')")
     */
    public function checkLoginUniqueAction(Request $request, AntiBruteforceLockerService $securityAntibruteforceCheckLogin)
    {
        $value = $request->request->get('value');
        $locked = $securityAntibruteforceCheckLogin->checkForLockout($request->getClientIp());

        if (!empty($locked)) {
            $this->lockoutLog($request, 'registration: login lockout', $value);

            return new Response('locked');
        }

        if ($value && is_string($value)) {
            $c = $this->usrRepository->findBy(['login' => $value], null, 1);

            if (count($c)) {
                return new Response('false');
            }

            return new Response('true');
        }

        return new Response('false');
    }

    public static function getCaptchaSessionKey(string $captcha): string
    {
        return 'captcha_valid_' . sha1($captcha);
    }

    /**
     * @Route("/user/check_email_2", name="aw_users_check_email", methods={"POST"}, options={"expose"=true})
     * @JsonDecode
     * @Security("is_granted('CSRF')")
     */
    public function checkEmailUniqueAction(Request $request, DesktopCaptchaResolver $captchaResolver, SessionInterface $session)
    {
        $value = $request->request->get('value');
        $token = $request->request->get('token');

        $captchaProvider = $captchaResolver->resolve($request);

        if (!$captchaProvider->getValidator()->validate($request->request->get("recaptcha") ?? '', $request->getClientIp() ?? '')) {
            $this->logger->info("invalid recaptcha while doing email existence check");

            return new Response($this->translator->trans(/** @Desc("Invalid security code, please try again") */ "invalid_captcha", [], "validators"));
        }

        $session->set(self::getCaptchaSessionKey($request->request->get("recaptcha") ?? ''), true);

        // ignore hacker, if token is missing
        if ($token !== 'fo32jge') {
            $this->logger->info("prevent email existence check");

            // return fake response
            return new Response(random_int(1, 100) > 99 ? 'false' : 'true');
        }

        $locked = $this->securityAntibruteforceCheckEmail->checkForLockout($request->getClientIp());

        if (!empty($locked)) {
            $this->lockoutLog($request, 'registration: email lockout', $value);

            return new Response('locked');
        }

        if ($value && is_string($value)) {
            $c = $this->usrRepository->findBy(['email' => $value], null, 1);

            if (count($c)) {
                return new Response('false');
            }

            return new Response('true');
        }

        return new Response('false');
    }

    /**
     * @Route("/user/check_business_name", name="aw_users_check_business_name_unique", methods={"POST"}, options={"expose"=true})
     * @JsonDecode
     * @Security("is_granted('CSRF')")
     */
    public function checkBusinessNameUniqueAction(Request $request)
    {
        $value = $request->request->get('value');
        $locked = $this->securityAntibruteforceCheckEmail->checkForLockout($request->getClientIp());

        if (!empty($locked)) {
            $this->lockoutLog($request, 'registration: business name lockout', $value);

            return new Response('locked');
        }

        if ($value && is_string($value)) {
            $c = $this->usrRepository->findBy(['company' => $value], null, 1);

            if (count($c)) {
                return new Response('false');
            }

            return new Response('true');
        }

        return new Response('false');
    }

    /**
     * @Security("is_granted('ROLE_USER')")
     * @Route("/user/edit{ext}", name="aw_user_edit", requirements={"ext" = "(\.php)?"}, options={"expose"=true})
     */
    public function redirectEditAction()
    {
        if ($this->authorizationChecker->isGranted('SITE_BUSINESS_AREA')) {
            return $this->redirectToRoute('aw_profile_overview_business');
        }

        return $this->redirectToRoute('aw_profile_overview');
    }

    /**
     * @Security("is_granted('ROLE_USER')")
     * @Route("/user/old-interface",
     *      name="aw_user_old_interface_switch",
     *      options={"expose"=true},
     * )
     */
    public function switchToOldInterfaceAction()
    {
        $user = $this->tokenStorage->getUser();
        $response = new RedirectResponse('/');

        if ($user->getInbeta()) {
            $user->setBetaapproved(false);
            $this->entityManager->persist($user);
            $this->entityManager->flush();
        }

        return $response;
    }

    /**
     * @Security("is_granted('ROLE_USER')")
     * @Route("/user/new-interface",
     *      name="aw_user_new_interface_switch",
     *      options={"expose"=true},
     * )
     */
    public function switchToNewInterfaceAction()
    {
        $user = $this->tokenStorage->getUser();

        $response = new RedirectResponse($this->router->generate('aw_home'));

        if ($user->getInbeta()) {
            $user->setBetaapproved(true);
            $this->entityManager->persist($user);
            $this->entityManager->flush();
        }

        return $response;
    }

    /**
     * @Route("/beta/{betaInvite}", name="aw_beta_invite", requirements={"betaInvite"="\w{10}"})
     */
    public function betaInviteAction(Request $request, $betaInvite)
    {
        $user = $this->tokenStorage->getUser();
        $route = 'aw_home';

        if ($user) {
            $user->setInbeta(true);
            $user->setBetaapproved(true);
            $this->entityManager->persist($user);
            $this->entityManager->flush();

            $route = 'aw_account_list';
            $this->userManager->checkUserWasInBeta();
        }

        return new RedirectResponse($this->router->generate($route));
    }

    /**
     * @Route("/user/sessions", name="aw_user_sessions")
     * @Security("is_granted('ROLE_USER') and is_granted('NOT_SITE_BUSINESS_AREA')")
     * @Template("@AwardWalletMain/Users/sessions.html.twig")
     */
    public function sessionsAction(
        Request $request,
        SessionListener $sessionListener,
        RememberMeTokenProvider $tokenProvider,
        string $vapidPublicKey,
        string $webpushIdParam
    ) {
        $user = $this->tokenStorage->getUser();
        $activitySess = $tokenProvider->fetchIdentificationByUserId($user->getUserid());
        $oldSess = $sessionListener->fetchOldSessions($user->getUserid());

        return [
            'sessionIps' => array_unique(array_column($activitySess, 'IP')),
            'identifications' => $sessionListener->groupSessions($activitySess, $request->getSession()->getId()),
            'sessions' => $sessionListener->groupSessions($oldSess, $request->getSession()->getId()),
            'vapid_public_key' => $vapidPublicKey,
            'webpush_id' => $webpushIdParam,
        ];
    }

    /**
     * @Route("/user/sessions/signouts", name="aw_user_sessions_signouts", methods={"POST"}, options={"expose"=true})
     * @Security("is_granted('ROLE_USER') and is_granted('CSRF') and is_granted('NOT_SITE_BUSINESS_AREA') and is_granted('NOT_USER_IMPERSONATED')")
     * @return JsonResponse
     * @throws
     */
    public function sessionSignoutsAction(Request $request, SessionListener $sessionListener)
    {
        $result = $sessionListener->invalidateAllButCurrent($this->tokenStorage->getUser()->getUserid(), $request->getSession()->getId());

        return new JsonResponse(['success' => $result]);
    }

    /**
     * @Route("/user/create-business-account", name="aw_user_convert_to_business")
     * @Security("is_granted('ROLE_USER') and is_granted('NOT_USER_IMPERSONATED')")
     * @Template("@AwardWalletMain/Users/convertToBusiness.html.twig")
     */
    public function convertToBusinessAction(Request $request, string $businessHost)
    {
        $businessUser = $this->usrRepository->getBusinessByUser($this->tokenStorage->getUser());

        if ($businessUser && !$this->authorizationChecker->isGranted('SITE_BUSINESS_AREA')) {
            return $this->redirect($this->generateUrl("aw_security_switch_site"));
        }

        if ($businessUser && $this->authorizationChecker->isGranted('SITE_BUSINESS_AREA')) {
            return $this->redirect($this->router->generate('aw_account_list', [], UrlGeneratorInterface::ABSOLUTE_PATH));
        }

        $this->userProfileWidget->setActiveItem('business');
        $userId = $this->tokenStorage->getUser()->getUserid();

        $form = $this->createFormBuilder()
            ->add('name', TextType::class, [
                /** @Desc("Company name") */
                'label' => 'company_name',
                'constraints' => [
                    new Assert\NotBlank(),
                    new Assert\Callback(function ($object, ExecutionContextInterface $context) use (&$userId) {
                        $usrRep = $this->usrRepository;

                        if ($object && $usrRep->findBy(['company' => $object])) {
                            $context->addViolation($this->translator->trans(/** @Desc("This company name already taken.") */
                                'company.already.taken',
                                [],
                                'validators'
                            ));
                        }

                        if ($object && $usrRep->findBy(['login' => $usrRep->createLogin($userId, $object)])) {
                            $context->addViolation($this->translator->trans(/** @Desc("The company name can’t be the same as you personal username.") */
                                'company.username.same',
                                [],
                                'validators'
                            ));
                        }
                    }),
                ], ])
            ->add('logo', FileType::class, [
                'required' => false,
                'constraints' => [
                    new Assert\Image([
                        'maxSize' => '2M',
                    ]),
                ],
            ])
            ->getForm();

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $usrRep = $this->usrRepository;

            $business = clone $this->tokenStorage->getUser();
            $business->setPass('disabled');
            $business->setAccountlevel(ACCOUNT_LEVEL_BUSINESS);
            $business->setFirstname('Business');
            $business->setLastname('Account');
            $business->setPictureext(null);
            $business->setPicturever(null);
            $business->setCompany($form->get('name')->getData());
            $business->setRefcode(RandomStr(ord('a'), ord('z'), 10));
            $business->setLogin($usrRep->createLogin($userId, $form->get('name')->getData()));
            $business->setEmail('b.' . $userId . '@awardwallet.com');
            $business->setSocialadid(null);
            $business->setEmailnewplans(0);
            $business->setEmailtcsubscribe(0);
            $business->setEmailrewards(0);
            $business->setEmailverified(0);
            $business->setCheckinreminder(0);
            $business->setMidname(null);
            $business->setItinerarycalendarcode(null);
            $business->clearSubscription();
            $business->setStripeCustomerId(null);
            $this->entityManager->persist($business);

            $businessInfo = new BusinessInfo($business, 0, 0, new \DateTime("+" . BusinessInfo::TRIAL_PERIOD_MONTH . " month"));
            $this->entityManager->persist($businessInfo);

            $link1 = new Useragent();
            $link1->setAccesslevel(UseragentRepository::ACCESS_WRITE);
            $link1->setIsapproved(1);
            $link1->setAgentid($business);
            $link1->setClientid($this->tokenStorage->getUser());
            $link1->setSharebydefault(0);
            $link1->setSharecode(RandomStr(ord('a'), ord('z'), 10));
            $this->entityManager->persist($link1);

            $link2 = new Useragent();
            $link2->setAccesslevel(UseragentRepository::ACCESS_ADMIN);
            $link2->setIsapproved(1);
            $link2->setClientid($business);
            $link2->setAgentid($this->tokenStorage->getUser());
            $link2->setSharebydefault(0);
            $link2->setSharecode(RandomStr(ord('a'), ord('z'), 10));
            $this->entityManager->persist($link2);
            $this->entityManager->flush();

            if ($form->get('logo')->getData()) {
                /** @var UploadedFile $file */
                $pic = file_get_contents($form->get('logo')->getData()->getPathname());
                $avatarCreator = new AvatarCreator($pic);
                $avatarCreator->createAvatarsForUser($business);
                $business->setPicturever($avatarCreator->getVersion());
                $business->setPictureext($avatarCreator->getExtension());
                $this->entityManager->persist($business);
                $this->entityManager->flush();
            }
        }

        return [
            'form' => $form->createView(),
            'saved' => isset($business),
            'business_host' => $businessHost,
        ];
    }

    /**
     * @Route("/user/delete", name="aw_user_delete", methods={"GET"}, options={"expose"=true})
     * @Security("is_granted('ROLE_USER')")
     */
    public function deleteAction(
        TranslatorInterface $translator,
        UsrRepository $userRepository,
        RouterInterface $router,
        Request $request,
        Environment $twigEnv
    ): Response {
        $twigEnv->addGlobal('webpack', true);

        $user = $this->tokenStorage->getBusinessUser();
        $business = $userRepository->getBusinessByUser($user);
        $issetBusinessAccount = $this->onPersonalAndBusinessAdmin($user); // check for business_admin: ok

        if ($this->authorizationChecker->isGranted('SITE_BUSINESS_AREA') && !$this->authorizationChecker->isGranted('USER_BUSINESS_ADMIN')) {
            throw new NotFoundHttpException();
        }

        $data = [
            'business-text1' => $issetBusinessAccount ?
                $translator->trans(
                    'user.delete.popup-text1' /* @Desc("your account happens to be an owner of <b>"%businessname%"</b> business.") */,
                    ['%businessName%' => htmlentities($business->getCompany())]
                )
                : '',
            'business-text3' =>
            $translator->trans(
                /** @Desc("To make sure you understand that please login to <a href='%href%'>business account</a> and edit your Account Access level to say <b class='red'>'No Access'</b>. After that you will be able to delete your personal account.") */ 'user.delete.popup-text3',
                ['%href%' => $router->generate('aw_security_switch_site', ["Goto" => '/members'], UrlGeneratorInterface::ABSOLUTE_URL)]
            ),
            'show-warning-popup' => $issetBusinessAccount ? 'true' : 'false',
            'isBusinessArea' => $this->authorizationChecker->isGranted('site_business_area') ? 'true' : 'false',
        ];

        if ($request->isXmlHttpRequest()) {
            return new JsonResponse($data);
        }

        $this->userProfileWidget->setActiveItem('delete');

        return $this->render('@AwardWalletMain/spa.html.twig', [
            'entrypoint' => 'user-settings',
            'data' => $data,
            'extendProfile' => true,
        ]);
    }

    /**
     * @Route("/user/delete", name="aw_user_delete_post", methods={"POST"})
     * @Security("is_granted('ROLE_USER')")
     * @JsonDecode
     */
    public function deletePostAction(
        Request $request,
        UserRemover $userRemover,
        MobileReauthenticatorHandler $mobileReauthenticatorHandler,
        UnremovableUsersParameter $unremovableUsersParameter,
        SessionInterface $session,
        CartRepository $cartRepository
    ): Response {
        $user = $this->tokenStorage->getBusinessUser();
        $action = Action::getDeleteAccountAction();

        if (!$this->authorizationChecker->isGranted('CSRF')) {
            return $this->json([
                'success' => false,
                'error' => 'CSRF failed',
            ], 403);
        }

        $reauthReponse = $mobileReauthenticatorHandler->handle(
            $action,
            $request,
            [],
            false
        );

        if ($reauthReponse) {
            return $reauthReponse;
        }

        if (in_array($user->getId(), $unremovableUsersParameter->get())) {
            return $this->json([
                'success' => false,
                'error' => $this->translator->trans('account_not_be_deleted', [], 'validators'),
            ]);
        }

        if ($this->onPersonalAndBusinessAdmin($user)) {
            return $this->json([
                'success' => false,
                'error' => $this->translator->trans(
                    /** @Desc("You can't delete your account because it is linked to a business account") */
                    'user.delete.linked_to_business',
                    [],
                    'validators'
                ),
            ]);
        }

        $reason = $request->request->get('reason');

        if (!is_string($reason) || empty($reason = \trim($reason))) {
            return $this->json([
                'success' => false,
                'error' => $this->translator->trans('notblank', [], 'validators'),
            ]);
        }

        $mobileReauthenticatorHandler->reset($action);

        if ($this->authorizationChecker->isGranted('USER_IMPERSONATED')) {
            $impersonator = Utils::getImpersonator($this->tokenStorage->getToken());

            return $this->json([
                'success' => false,
                'error' => sprintf(
                    'You are impersonated as "%s". You can\'t use this feature.',
                    $impersonator ?: 'unknown'
                ),
            ]);
        }

        $isAppleSubscriber =
            ($user->getSubscription() == Usr::SUBSCRIPTION_MOBILE)
            && $user->getSubscriptionType() == Usr::SUBSCRIPTION_TYPE_AWPLUS
            && ($activeAwSubscription = $cartRepository->getActiveAwSubscription($user))
            && ($activeAwSubscription->getPaymenttype() == Cart::PAYMENTTYPE_APPSTORE);
        $userRemover->deleteUser($user, $reason);
        $this->forward('AwardWallet\MainBundle\Controller\LogoutController::logoutAction');
        $session->set("user_deleted", true);

        return new JsonResponse(['success' => true, 'isAppleSubscriber' => $isAppleSubscriber]);
    }

    /**
     * @Route("/user/deleted", name="aw_user_deleted", methods={"GET"})
     * @JsonDecode
     */
    public function deletedAction(Request $request, SessionInterface $session)
    {
        if (!$session->has("user_deleted")) {
            throw new NotFoundHttpException();
        }

        return $this->render('@AwardWalletMain/Users/deleted.html.twig', ["username" => "", 'isAppleSubscriber' => $request->query->has('isAppleSubscriber')]);
    }

    public function changePasswordAction()
    {
    }

    /**
     * @return bool|string
     */
    protected function restorePasswordRequest(Request $request, $username = null)
    {
        return $this->userManager->sendRestorePasswordEmail($request, $username ?: $request->get('username'));
    }

    private function lockoutLog(Request $request, $message, $value)
    {
        $page = $request->headers->get('referer', 'Empty Referer');
        $host = $request->headers->get('host');
        $ip = $request->getClientIp();
        $session = $request->getSession() ? substr($request->getSession()->getId(), -4) : 'unknown';
        $browser = $request->headers->get('user-agent', 'unknown');

        $logger = $this->logger;

        $logger->warning($message, [
            'Login/Email' => $value,
            'Page' => $page,
            'Host' => $host,
            'IP' => $ip,
            'Session' => $session,
            'UA' => $browser,
        ]);
    }

    private function onPersonalAndBusinessAdmin(Usr $user): bool
    {
        return
            !$this->authorizationChecker->isGranted('SITE_BUSINESS_AREA')
            && $this->usrRepository->isUserBusinessAdmin($user);
    }
}
