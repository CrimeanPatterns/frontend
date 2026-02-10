<?php

namespace AwardWallet\MainBundle\Controller\Profile;

use AwardWallet\MainBundle\Entity\Country;
use AwardWallet\MainBundle\Entity\Donotsend;
use AwardWallet\MainBundle\Entity\MobileDevice;
use AwardWallet\MainBundle\Entity\Repositories\UsrRepository;
use AwardWallet\MainBundle\Entity\Usr;
use AwardWallet\MainBundle\Event\UserPushDeviceChangedEvent;
use AwardWallet\MainBundle\Form\Handler\GenericRequestHandler\Handler;
use AwardWallet\MainBundle\Form\Type\NotificationBlogType;
use AwardWallet\MainBundle\Form\Type\NotificationType;
use AwardWallet\MainBundle\FrameworkExtension\AwTokenStorageInterface;
use AwardWallet\MainBundle\FrameworkExtension\Exceptions\UserErrorException;
use AwardWallet\MainBundle\FrameworkExtension\Mailer\Mailer;
use AwardWallet\MainBundle\FrameworkExtension\Mailer\Template\User\TestEmail;
use AwardWallet\MainBundle\Globals\UserAgentUtils;
use AwardWallet\MainBundle\Manager\MobileDeviceManager;
use AwardWallet\MainBundle\Security\AntiBruteforceLockerService;
use AwardWallet\MainBundle\Service\GeoLocation\GeoLocation;
use AwardWallet\MainBundle\Service\Notification\Content;
use AwardWallet\MainBundle\Service\Notification\Sender;
use AwardWallet\MainBundle\Service\Notification\Unsubscriber;
use AwardWallet\MainBundle\Service\PageVisitLogger;
use AwardWallet\MainBundle\Service\SecureLink;
use AwardWallet\MainBundle\Service\WebPush\SafariTokenGenerator;
use AwardWallet\MainBundle\Worker\PushNotification\DTO\InterruptionLevel;
use AwardWallet\MainBundle\Worker\PushNotification\DTO\Options;
use AwardWallet\WidgetBundle\Widget\UserProfileWidget;
use Doctrine\DBAL\Connection;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Security;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\Form\Extension\Core\Type\EmailType;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Session\SessionInterface;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Routing\RouterInterface;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

/**
 * @Route("/")
 */
class NotificationsController extends AbstractController
{
    public const MESSAGES = [
        "Your smile is contagious!",
        "You look great today!",
        "You're a smart cookie!",
        "We bet you make babies smile!",
        "You have impeccable manners!",
        "We like your style!",
        "You have the best laugh!",
        "We appreciate you!",
        "You're awesome!",
        "You light up the room!",
        "You deserve a hug right now!",
        "You're more helpful than you realize!",
        "You have a great sense of humor!",
        "You've got all the right moves!",
        "Is that your picture next to \"charming\" in the dictionary?",
        "On a scale from 1 to 10, you're an 11!",
        "If cartoon bluebirds were real, a bunch of them would be sitting on your shoulders singing right now!",
        "You're like sunshine on a rainy day!",
        "You bring out the best in other people!",
        "Everything would be better if more people were like you!",
        "Being around you makes everything better!",
        "Colors seem brighter when you're around!",
        "You're wonderful!",
        "Jokes are funnier when you tell them!",
        "You're better than a triple-scoop ice cream cone! With sprinkles!",
        "You're one of a kind!",
        "Our community is better because you're in it!",
        "You have the best ideas!",
        "The people you love are lucky to have you in their lives!",
        "Your creative potential seems limitless!",
        "Any team would be lucky to have you on it!",
        "There's ordinary, and then there's you!",
        "You're even better than a unicorn, because you're real!",
        "How do you keep being so funny and making everyone laugh?",
    ];

    private MobileDeviceManager $deviceManager;
    private AuthorizationCheckerInterface $authorizationChecker;
    private SessionInterface $session;
    private RouterInterface $router;
    private TranslatorInterface $translator;
    private AwTokenStorageInterface $tokenStorage;
    private Unsubscriber $unsubscriber;
    private SecureLink $secureLink;
    private EntityManagerInterface $entityManager;
    private GeoLocation $geoLocation;
    private Handler $notificationHandlerDesktop;
    private string $vapidPublicKey;
    private string $webpushId;

    private UsrRepository $userRepository;
    private SafariTokenGenerator $safariTokenGenerator;

    public function __construct(
        MobileDeviceManager $deviceManager,
        AuthorizationCheckerInterface $authorizationChecker,
        SessionInterface $session,
        RouterInterface $router,
        TranslatorInterface $translator,
        AwTokenStorageInterface $tokenStorage,
        Unsubscriber $unsubscriber,
        SecureLink $secureLink,
        EntityManagerInterface $entityManager,
        GeoLocation $geoLocation,
        Handler $notificationHandlerDesktop,
        string $vapidPublicKey,
        string $webpushIdParam,
        UsrRepository $userRepository,
        SafariTokenGenerator $safariTokenGenerator
    ) {
        $this->deviceManager = $deviceManager;
        $this->authorizationChecker = $authorizationChecker;
        $this->session = $session;
        $this->router = $router;
        $this->translator = $translator;
        $this->tokenStorage = $tokenStorage;
        $this->unsubscriber = $unsubscriber;
        $this->secureLink = $secureLink;
        $this->entityManager = $entityManager;
        $this->geoLocation = $geoLocation;
        $this->notificationHandlerDesktop = $notificationHandlerDesktop;
        $this->vapidPublicKey = $vapidPublicKey;
        $this->webpushId = $webpushIdParam;
        $this->userRepository = $userRepository;
        $this->safariTokenGenerator = $safariTokenGenerator;
    }

    /**
     * @Route("/unsubscribe", name="aw_profile_unsubscribe")
     * @Route("/unsubscribe", host="%business_host%", name="aw_profile_unsubscribe_business")
     */
    public function unsubscribeAction(Request $request, LoggerInterface $logger)
    {
        $session = $request->getSession();
        $isBusinessArea = $this->authorizationChecker->isGranted('SITE_BUSINESS_AREA');
        $email = $request->query->get('email');
        $code = $request->query->get('code');

        if ((isset($email) && !is_string($email)) || (isset($code) && !is_string($code))) {
            $logger->info("email or code not found");

            throw $this->createNotFoundException();
        }

        if (!$session->has('unsubscribe.email') || !$session->has('unsubscribe.code') || !empty($email) || !empty($code)) {
            if (empty($email) || empty($code)) {
                $logger->info(
                    'unsubscribe link without email or code',
                    [
                        'query_email' => $email,
                        'query_code' => $code,
                        'session_email' => $session->get('unsubscribe.email'),
                        'session_code' => $session->get('unsubscribe.code'),
                        'referer' => $request->headers->get('referer'),
                    ]
                );
            }

            $session->set('unsubscribe.email', $email);
            $session->set('unsubscribe.code', $code);
            $this->session->migrate();

            $logger->info("redirecting to " . $request->attributes->get('_route'));

            return $this->redirect($this->router->generate($request->attributes->get('_route')));
        }

        $email = $session->get('unsubscribe.email');
        $code = $session->get('unsubscribe.code');

        if (empty($email) || empty($code) || !($checked = $this->secureLink->checkUnsubscribeHash($email, $code, $isBusinessArea))) {
            // old business link
            if ($isBusinessArea && !empty($email) && !empty($code) && $this->secureLink->checkUnsubscribeOldBusinessHash($email, $code)) {
                $logger->info("redirecting to old business link");

                return $this->redirect($this->secureLink->protectUnsubscribeUrl($email));
            }

            $logger->warning(
                'unsubscribe link with invalid email or code',
                [
                    'email' => $email,
                    'code' => $code,
                    'is_business' => $isBusinessArea,
                    'checked' => $checked ?? null,
                    'referer' => $request->headers->get('referer'),
                ]
            );

            throw new UserErrorException($this->translator->trans(/** @Desc("Invalid link") */ 'exception.invalid-link'));
        }

        $userRep = $this->getDoctrine()->getRepository(\AwardWallet\MainBundle\Entity\Usr::class);
        /** @var Usr $user */
        $user = $userRep->findOneByEmail($email);

        if ($user) {
            if ($user->isBusiness()) {
                throw new UserErrorException($this->translator->trans(/** @Desc("Invalid link") */ 'exception.invalid-link'));
            }

            if ($isBusinessArea) {
                $business = $userRep->getBusinessByUser($user);

                if (!isset($business)) {
                    throw new UserErrorException($this->translator->trans(/** @Desc("Business not found") */ 'exception.business-not-found'));
                } elseif (!$business->isBooker()) {
                    $logger->info("redirecting to not booker business link");

                    return $this->redirect($this->secureLink->protectUnsubscribeUrl($email));
                }
            }

            return $this->notificationsAction($request, $user, $logger);
        } else {
            return $this->doNotSendListAction($request, $email, $logger);
        }
    }

    /**
     * @Route("/user/notifications", name="aw_profile_notifications")
     * @Security("is_granted('ROLE_USER')")
     */
    public function editNotifications(
        Request $request,
        UserProfileWidget $userProfileWidget,
        LoggerInterface $logger,
        PageVisitLogger $pageVisitLogger
    ) {
        if (
            $this->authorizationChecker->isGranted('SITE_BUSINESS_AREA')
            && !$this->authorizationChecker->isGranted('SITE_BOOKER_AREA')
        ) {
            throw new NotFoundHttpException();
        }
        $userProfileWidget->setActiveItem('notifications');
        $pageVisitLogger->log(PageVisitLogger::PAGE_EDIT_EMAIL_NOTIFICATIONS);

        return $this->notificationsAction($request, $this->tokenStorage->getToken()->getUser(), $logger, true);
    }

    /**
     * @Route("/user/notifications/blog", name="aw_profile_blog_notifications")
     * @Security("is_granted('ROLE_USER') and is_granted('NOT_SITE_BUSINESS_AREA')")
     * @Template("@AwardWalletMain/Profile/Notifications/blog.html.twig")
     */
    public function editBlogNotifications(
        Request $request,
        UserProfileWidget $userProfileWidget,
        EntityManagerInterface $entityManager,
        TranslatorInterface $translator
    ) {
        $userProfileWidget->setActiveItem('notifications');
        $user = $this->tokenStorage->getToken()->getUser();
        $form = $this->createForm(NotificationBlogType::class, $user);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $notify = $form->getData();
            $entityManager->flush();

            $this->session->getFlashBag()->add('notice', $translator->trans('notice.notifications-success-changed'));

            return $this->redirect($this->router->generate('aw_profile_blog_notifications'));
        }

        return [
            'form' => $form->createView(),
            'vapid_public_key' => $this->vapidPublicKey,
            'webpush_id' => $this->webpushId,
        ];
    }

    /**
     * @Route("/user/notifications/disable/{code}", name="aw_mobile_device_unsubscribe", methods={"POST"}, requirements={"code" = ".+"}, options={"expose"=true})
     * @Security("is_granted('ROLE_USER') and is_granted('CSRF')")
     * @return JsonResponse
     */
    public function unsubscribeDeviceAction($code, EventDispatcherInterface $eventDispatcher)
    {
        $info = $this->unsubscriber->extractInfoFromCode($code);

        if (empty($info) || empty($info->device) || $info->device->getUser() !== $this->tokenStorage->getToken()->getUser()) {
            return new JsonResponse(["status" => "invalid_code"]);
        }

        $this->entityManager->remove($info->device);
        $this->entityManager->flush();

        $eventDispatcher->dispatch(
            new UserPushDeviceChangedEvent($info->device->getUser())
        );

        return new JsonResponse(["status" => "unsubscribed"]);
    }

    /**
     * @Route("/user/notifications/send/{code}", name="aw_device_send_push", methods={"POST"}, requirements={"code" = ".+"}, options={"expose"=true})
     * @Security("is_granted('ROLE_USER') and is_granted('CSRF')")
     * @return JsonResponse
     */
    public function sendPushAction($code, Sender $sender)
    {
        $info = $this->unsubscriber->extractInfoFromCode($code);

        if (empty($info) || empty($info->device) || $info->device->getUser() !== $this->tokenStorage->getToken()->getUser()) {
            return new JsonResponse(["status" => "invalid_code"]);
        }

        $title = $info->device->isDesktop() ? 'AwardWallet Test Notification' : null;

        $message = new Content(
            $title,
            self::MESSAGES[array_rand(self::MESSAGES)],
            Content::TYPE_PRODUCT_UPDATES,
            $this->router->generate('aw_home'),
            (new Options())
                ->setInterruptionLevel(InterruptionLevel::TIME_SENSITIVE)
        );

        return new JsonResponse([
            "sent" => $sender->send($message, [$info->device]),
        ]);
    }

    /**
     * @Route("/user/notifications/send-test-email", name="aw_send_test_email", methods={"POST"}, options={"expose"=true})
     * @Security("is_granted('ROLE_USER') and is_granted('CSRF')")
     * @return JsonResponse
     */
    public function sendTestEmailAction(
        Request $request,
        \Memcached $memcached,
        LoggerInterface $securityLogger,
        Mailer $mailer
    ) {
        $user = $this->tokenStorage->getToken()->getUser();
        $locker = new AntiBruteforceLockerService($memcached, "test_email", 60, 60, 3, "too many attempts", $securityLogger);

        if (!empty($locker->checkForLockout($user->getEmail()))) {
            return new JsonResponse(["sent" => false]);
        }

        $template = new TestEmail($user);
        $template->message = self::MESSAGES[array_rand(self::MESSAGES)];

        $message = $mailer->getMessageByTemplate($template);

        return new JsonResponse([
            "sent" => $mailer->send($message, [Mailer::OPTION_SKIP_STAT => true, Mailer::OPTION_SKIP_DONOTSEND => true]),
        ]);
    }

    /**
     * @Route("/user/notifications/set-alias/{code}", name="aw_device_set_alias", methods={"POST"}, requirements={"code" = ".+"}, options={"expose"=true})
     * @Security("is_granted('ROLE_USER') and is_granted('CSRF')")
     * @return JsonResponse
     */
    public function setAliasAction(Request $request, $code)
    {
        $info = $this->unsubscriber->extractInfoFromCode($code);

        if (empty($info) || empty($info->device) || $info->device->getUser() !== $this->tokenStorage->getToken()->getUser()) {
            return new JsonResponse(["status" => "invalid_code"]);
        }

        $info->device->setAlias(
            $request->request->get('alias')
        );
        $this->getDoctrine()->getManager()->flush();

        return new JsonResponse(["status" => "success"]);
    }

    /**
     * @Route("/user/notifications/check/browser", name="aw_notifications_check_browser", options={"expose"=true})
     * @Security("is_granted('ROLE_USER')")
     * @return JsonResponse
     */
    public function checkBrowserSubscriptionAction(
        Request $request,
        Connection $connection,
        AwTokenStorageInterface $tokenStorage
    ) {
        if ($this->authorizationChecker->isGranted('USER_IMPERSONATED')) {
            return new JsonResponse(['found' => null]);
        }

        $browser = UserAgentUtils::getBrowser($request->headers->get('User-Agent'));

        if ('Edge' === $browser['browser']) {
            $browser['browser'] = 'Edg';
        }

        $is = $connection->fetchOne('
            SELECT 1
            FROM MobileDevice
            WHERE
                    UserID = ' . $tokenStorage->getUser()->getId() . '
                AND Tracked = 1
                AND DeviceType IN (' . implode(',', MobileDevice::TYPES_DESKTOP) . ')
                -- AND UserAgent LIKE ' . $connection->quote('%' . $browser['browser'] . '%') . '
                -- AND UserAgent LIKE ' . $connection->quote('%' . $browser['platform'] . '%') . '
        ');

        return new JsonResponse(['found' => !empty($is)]);
    }

    private function notificationsAction(Request $request, Usr $user, LoggerInterface $logger, $redirect = false)
    {
        $logger->info("notificationsAction");
        $isBusiness = $this->authorizationChecker->isGranted('SITE_BUSINESS_AREA');
        $isTrial = $this->userRepository->isTrialAccount($user);
        $isUsClientIp = $this->geoLocation->getCountryIdByIp($request->getClientIp()) === Country::UNITED_STATES;
        $freeVersion = ($user->isFree() || $isTrial) && $user->isUs() && $isUsClientIp;

        $userDevices = $this
            ->getDoctrine()
            ->getRepository(\AwardWallet\MainBundle\Entity\MobileDevice::class)
            ->findBy(['userId' => $user, 'tracked' => true]);

        $mobileDevices = [];
        $desktopDevices = [];
        $unsubscribeCodes = [];

        foreach ($userDevices as $device) {
            if ($device->isMobile()) {
                $mobileDevices[] = $device;
            } else {
                $desktopDevices[] = $device;
            }
            $unsubscribeCodes[$device->getDeviceKey()] = $this->unsubscriber->getUnsubscribeCode($device);
        }

        $form = $this->createForm(NotificationType::class, $user, [
            'isBusiness' => $isBusiness,
            'freeVersion' => $freeVersion,
        ]);

        if ($this->notificationHandlerDesktop->handleRequest($form, $request)) {
            $this->getDoctrine()->getManager()->flush();
            $ok = true;

            if ($redirect) {
                $this->session->getFlashBag()->add(
                    'notice',
                    $this->translator->trans(/** @Desc("Your notifications preferences have been successfully updated") */ 'notice.notifications-success-changed')
                );

                $logger->info("redirecting to profile");

                return $this->redirect($this->router->generate('aw_profile_overview'));
            }
        }

        return $this->render("@AwardWalletMain/Profile/Notifications/index.html.twig", [
            'form' => $form->createView(),
            'success' => isset($ok),
            'errors' => $form->getErrors(true),
            'user' => $user,
            'mobileDevices' => $mobileDevices,
            'desktopDevices' => $desktopDevices,
            'locationService' => $this->geoLocation,
            'deviceManager' => $this->deviceManager,
            'unsubscribeCodes' => $unsubscribeCodes,
            'vapid_public_key' => $this->vapidPublicKey,
            'webpush_id' => $this->webpushId,
            'browser' => UserAgentUtils::getBrowser($request->headers->get('User-Agent')),
            'freeVersion' => $freeVersion,
            'safariToken' => $this->safariTokenGenerator->getToken(),
        ]);
    }

    private function doNotSendListAction(Request $request, $email, LoggerInterface $logger)
    {
        $logger->info("doNotSendListAction");
        $dns = new Donotsend($email, $request->getClientIp());

        $form = $this->createFormBuilder($dns)
            ->add('email', EmailType::class, [
                'label' => $this->translator->trans('login.email'),
                'attr' => ['readonly' => 'readonly'],
            ])->getForm();

        if ($request->isMethod('POST')) {
            $rep = $this->entityManager->getRepository(\AwardWallet\MainBundle\Entity\Donotsend::class);
            $found = $rep->findByEmail($dns->getEmail());

            if (!$found) {
                $this->entityManager->persist($dns);
                $this->entityManager->flush();
            }
            $ok = true;
        }

        return $this->render("@AwardWalletMain/Profile/Notifications/doNotSendList.html.twig", [
            'form' => $form->createView(),
            'success' => isset($ok),
            'data' => $dns,
        ]);
    }
}
