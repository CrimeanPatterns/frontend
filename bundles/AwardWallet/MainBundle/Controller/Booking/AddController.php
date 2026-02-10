<?php

namespace AwardWallet\MainBundle\Controller\Booking;

use AwardWallet\MainBundle\Entity\AbPassenger;
use AwardWallet\MainBundle\Entity\AbRequest;
use AwardWallet\MainBundle\Entity\Usr;
use AwardWallet\MainBundle\Form\Type\AbRequestType;
use AwardWallet\MainBundle\FrameworkExtension\Serializer\AbRequestDenormalizer;
use AwardWallet\MainBundle\FrameworkExtension\Serializer\CamelCaseNameConverter;
use AwardWallet\MainBundle\Manager\BookingRequestManager;
use AwardWallet\MainBundle\Manager\LogoManager;
use AwardWallet\MainBundle\Manager\ProgramShareManager;
use AwardWallet\MainBundle\Manager\UserManager;
use AwardWallet\MainBundle\Parameter\DefaultBookerParameter;
use AwardWallet\MainBundle\Security\AntiBruteforceLockerService;
use AwardWallet\MainBundle\Security\Captcha\Resolver\DesktopCaptchaResolver;
use AwardWallet\MainBundle\Security\Captcha\Validator\RecaptchaValidator;
use AwardWallet\MainBundle\Service\GoogleAnalytics4;
use AwardWallet\MainBundle\Service\PageVisitLogger;
use AwardWallet\WidgetBundle\Widget\BookingLeftMenuWidget;
use Doctrine\Common\Annotations\AnnotationReader;
use Psr\Log\LoggerInterface;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\ParamConverter;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Security;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Form\FormError;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Session\SessionInterface;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Routing\RouterInterface;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;
use Symfony\Component\Serializer\Encoder\JsonEncoder;
use Symfony\Component\Serializer\Mapping\Factory\ClassMetadataFactory;
use Symfony\Component\Serializer\Mapping\Loader\AnnotationLoader;
use Symfony\Component\Serializer\Normalizer\AbstractNormalizer;
use Symfony\Component\Serializer\Serializer;
use Symfony\Component\Validator\ConstraintViolationInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

class AddController extends AbstractController
{
    private AuthorizationCheckerInterface $authorizationChecker;
    private BookingRequestManager $bookingRequestManager;
    private LogoManager $logoManager;
    private RouterInterface $router;
    private LoggerInterface $logger;
    private ProgramShareManager $programShareManager;

    public function __construct(
        AuthorizationCheckerInterface $authorizationChecker,
        BookingRequestManager $bookingRequestManager,
        LogoManager $logoManager,
        RouterInterface $router,
        LoggerInterface $logger,
        ProgramShareManager $programShareManager
    ) {
        $this->authorizationChecker = $authorizationChecker;
        $this->bookingRequestManager = $bookingRequestManager;
        $this->logoManager = $logoManager;
        $this->router = $router;
        $this->logger = $logger;
        $this->programShareManager = $programShareManager;
    }

    /**
     * @Route(
     *     "/awardBooking/add",
     *     name="aw_booking_add_index",
     *     defaults={"_canonical" = "aw_booking_add_index_locale", "_alternate" = "aw_booking_add_index_locale"}
     * )
     * @Route(
     *     "/{_locale}/awardBooking/add",
     *     name="aw_booking_add_index_locale",
     *     requirements={"_locale"="%route_locales%"},
     *     defaults={"_locale"="%locale%", "_canonical" = "aw_booking_add_index_locale", "_alternate" = "aw_booking_add_index_locale"}
     * )
     */
    public function indexAction(
        Request $request,
        DefaultBookerParameter $defaultBookerParameter,
        UserManager $userManager,
        \Memcached $memcached,
        TranslatorInterface $translator,
        RecaptchaValidator $recaptchaValidator,
        GoogleAnalytics4 $googleAnalytics,
        SessionInterface $session,
        BookingLeftMenuWidget $bookingLeftMenu,
        DesktopCaptchaResolver $captchaResolver,
        PageVisitLogger $pageVisitLogger,
        $requiresChannel,
        $host
    ) {
        $isBusiness = $this->authorizationChecker->isGranted('SITE_BUSINESS_AREA');
        $isBooker = $this->authorizationChecker->isGranted('USER_BOOKING_PARTNER');
        $bookingLeftMenu->setActiveItem("add");

        // access
        if (
            ($isBusiness && !$isBooker)
            || ($isBusiness && !$this->authorizationChecker->isGranted('BUSINESS_ACCOUNTS'))
            || ($request->isMethod('POST') && !$this->validReferer($request))
        ) {
            throw new AccessDeniedException();
        }

        /** @var \AwardWallet\MainBundle\Entity\Usr $user */
        $user = $this->getUser();

        if ($user && $isBooker) {
            $user = $this->getDoctrine()->getRepository(\AwardWallet\MainBundle\Entity\Usr::class)->getBusinessByUser($user);
        }
        // empty request
        $abRequest = $this->bookingRequestManager->getEmptyBookingRequest([
            'for_booker' => $isBooker,
            'for_partner' => (bool) $request->request->get('partner'),
        ]);

        // ref
        $siteAd = null;

        if (!$isBooker) {
            $ref = $request->getSession()->get('ref');
            $siteAdRep = $this->getDoctrine()->getRepository(\AwardWallet\MainBundle\Entity\Sitead::class);

            if (!empty($ref)) {
                $this->logger->info("session has ref $ref");
                $siteAd = $siteAdRep->find($ref);
            }

            $isBlockedBooker = fn (Usr $booker): bool => $booker->getBusinessInfo()->isBlocked() || ($booker->getBookerInfo() && $booker->getBookerInfo()->isBlocked());

            if ($siteAd && ($booker = $siteAd->getBooker()) && $isBlockedBooker($booker)) {
                $this->logger->info("booker ref $ref is blocked");
                $siteAd = null;
            }

            if ($siteAd) {
                $abRequest->setSiteAd($siteAd);
                $this->logger->info("set site ad $ref");
            }

            if ($siteAd && $siteAd->getBooker()) {
                $this->logger->info("set booker from ref");
                $abRequest->setBooker($siteAd->getBooker()); // set booker from ref
            } elseif ($user && !$isBusiness && $user->getOwnedByBusiness() && $user->getOwnedByBusiness()->isBooker() && !$isBlockedBooker($user->getOwnedByBusiness())) {
                $abRequest->setBooker($user->getOwnedByBusiness()); // set booker from user's came from
            } elseif ($user && !$isBusiness && $user->getDefaultBooker() && !$isBlockedBooker($user->getDefaultBooker())) {
                $abRequest->setBooker($user->getDefaultBooker()); // set booker from user first booking request
            } else {
                // set default booker
            }
        }

        $loginRequired = false;
        $autoRecover = $request->query->get('recover');

        $this->populateFromJson($request, $abRequest);
        $this->logoManager->setBookingRequest($abRequest);

        $form = $this->getAbRequestForm($abRequest, $user);

        if ($request->isMethod('post')) {
            $form->handleRequest($request);
        }

        $captchaProvider = $captchaResolver->resolve($request);

        if ($form->isSubmitted()) {
            $locker = new AntiBruteforceLockerService($memcached, "booking_add", 60, 5, 5, $translator->trans("connection.user_lockout"));
            $userIp = $request->getClientIp();
            $error = $locker->checkForLockout($userIp);

            if (!empty($error)) {
                $form->addError(new FormError($error));
            }

            if (!$user && !($form->isSubmitted() && $form->isValid()) && $form->get('User')->getErrors(true)->count() === 0) {
                // Register or login User
                if (!$captchaProvider->getValidator()->validate($request->request->get("recaptcha") ?? '', $request->getClientIp() ?? '')) {
                    $form->addError(new FormError($translator->trans("invalid_captcha", [], "validators")));
                } else {
                    $newUser = $abRequest->getUser();

                    if (
                        $newUser
                        && $userManager->checkUniqueUser($newUser)
                    ) {
                        $formPassword = $request->get('booking_request')['User']['pass']['Password'];
                        $formConfirmPassword = $request->get('booking_request')['User']['pass']['ConfirmPassword'];

                        if ($formPassword != $formConfirmPassword) {
                            $form->addError(new FormError(''));
                        } else {
                            // No need to send an email?
                            $userManager->registerUser($newUser, $request, true, false, false);
                            $userManager->loadToken($newUser, true);

                            // TODO: GA4 redo
                            /*
                            $googleAnalytics->setEventCategory('user')
                                ->setEventAction('registered')
                                ->setEventLabel('desktop-booking')
                                ->setEventValue(1)
                                ->setDataSource('desktop')
                                ->sendEvent();
                            */

                            $url = $this->generateUrl($request->get('_route'), ['recover' => 'true']) . '#tab2';

                            return $this->redirect($url);
                        }
                    }
                }
            }
        }

        if ($form->isSubmitted() && $form->isValid()) {
            $this->catchErrors($abRequest);

            // Register or login User
            if (!$user) {
                $user = $abRequest->getUser();
                // No need to send an email?
                $userManager->registerUser($user, $request, true, false, false);

                // TODO: GA4 redo
                /*
                $googleAnalytics->setEventCategory('user')
                    ->setEventAction('registered')
                    ->setEventLabel('desktop-booking')
                    ->setEventValue($user->getUserid())
                    ->setDataSource('desktop')
                    ->sendEvent();
                */

                $loginRequired = true;
            }

            $this->bookingRequestManager->create($abRequest);
            $this->bookingRequestManager->flush();

            if ($abRequest->getBooker()->getBookerInfo()->isAutoreplyInvoiceRequired()) {
                $this->bookingRequestManager->addAutoReplyInvoice($abRequest);
            }

            // first request with ref
            if (!$isBooker && $user && !$user->getDefaultBooker() && $siteAd && $siteAd->getBooker()) {
                $user->setDefaultBooker($abRequest->getBooker());
                $this->getDoctrine()->getManager()->flush($user);
            }

            $response = $this->redirect(
                $this->router->generate('aw_booking_view_index', ['id' => $abRequest->getAbRequestID()]) . '#'
            );

            if ($loginRequired) {
                $userManager->loadToken($user, true);
            }

            if (!$isBooker) {
                $session->getFlashBag()->add('new_booking_request', true);
            }

            if (!$isBooker) {
                $this->shareAccounts($abRequest);
            }

            return $response;
        }

        $publicLink = null;

        if ($isBooker) {
            $publicLink = $this->bookingRequestManager->getPublicLink(
                $host,
                $requiresChannel
            );
        }

        $errors = [];

        if (count($form->getErrors()) && !$user) {
            foreach ($form->getErrors() as $i => $error) {
                /** @var ConstraintViolationInterface $cause */
                $cause = $error->getCause();

                if ($cause && $cause instanceof ConstraintViolationInterface) {
                    if ($cause->getPropertyPath() == 'data.ContactEmail') {
                    } elseif ($cause->getPropertyPath() == 'data.ContactPhone') {
                        $form->get('User')->get('phone1')->addError($error);
                    } else {
                        $errors[] = $error;
                    }
                }
            }
        }
        $pageVisitLogger->log(PageVisitLogger::PAGE_AWARD_BOOKINGS);

        return $this->render('@AwardWalletMain/Booking/Add/index.html.twig', [
            'form' => $form->createView(),
            'errors' => $errors,
            'request' => $abRequest,
            'bookerInfo' => $abRequest->getBooker()->getBookerInfo(),
            'publicLink' => $publicLink,
            'autoRecover' => $autoRecover,
            'captcha_provider' => $captchaProvider,
        ]);
    }

    /**
     * @Security("is_granted('ROLE_USER')")
     * @Route("/awardBooking/get_contactinfo", name="aw_booking_add_getcontactinfo", options={"expose" = true})
     */
    public function getContactInfoAction(Request $request)
    {
        $queryRequest = $request->query->get('booking_request');

        if (is_array($queryRequest) && isset($queryRequest['User'])) {
            $data = $queryRequest['User'];

            if (isset($data['firstname'], $data['lastname'], $data['email']['Email'], $data['phone1'])) {
                return $this->render('@AwardWalletMain/Booking/Add/ajaxLogin.html.twig', [
                    'name' => trim($data['firstname'] . ' ' . $data['lastname']),
                    'email' => $data['email']['Email'],
                    'phone' => $data['phone1'],
                ]);
            }
        }

        throw new AccessDeniedException();
    }

    /**
     * @Route("/awardBooking/edit/{id}", name="aw_booking_add_edit", requirements={"id" = "\d+"}, options={"expose" = true})
     * @Security("is_granted('EDIT', abRequest)")
     * @ParamConverter("abRequest", class="AwardWalletMainBundle:AbRequest")
     */
    public function editAction(AbRequest $abRequest, Request $httpRequest, $requiresChannel, $host)
    {
        if ($httpRequest->isMethod('POST') && !$this->validReferer($httpRequest)) {
            throw new AccessDeniedException();
        }

        $isBooker = $this->authorizationChecker->isGranted('USER_BOOKING_PARTNER');
        /** @var Usr $user */
        $user = $this->getUser();

        if ($user && $isBooker) {
            $user = $this->getDoctrine()->getRepository(\AwardWallet\MainBundle\Entity\Usr::class)->getBusinessByUser($user);
        }

        $this->logoManager->setBookingRequest($abRequest);

        $form = $this->createForm(AbRequestType::class, $abRequest, [
            'user' => $user,
            'booker' => $abRequest->getBooker(),
            'attr' => ['id' => 'booking_request_new'],
            'by_booker' => $abRequest->getByBooker(),
        ]);

        $oldEmail = $abRequest->getContactEmail();

        $form->handleRequest($httpRequest);

        if ($form->isSubmitted() && $form->isValid()) {
            $this->catchErrors($abRequest);
            $this->bookingRequestManager->update($abRequest);

            if ($abRequest->getContactEmail() != $oldEmail && !$abRequest->getByBooker()) {
                $abRequest->setStatus(AbRequest::BOOKING_STATUS_NOT_VERIFIED);
                $this->bookingRequestManager->sendEmailOnNewRequest($abRequest);
            }
            $this->bookingRequestManager->flush();

            if (!$isBooker) {
                $this->shareAccounts($abRequest);
            }

            return $this->redirect(
                $this->router->generate('aw_booking_view_index', ['id' => $abRequest->getAbRequestID()]) . '#'
            );
        }

        $publicLink = null;

        if ($isBooker) {
            $publicLink = $this->bookingRequestManager->getPublicLink(
                $host,
                $requiresChannel
            );
        }

        return $this->render('@AwardWalletMain/Booking/Add/index.html.twig', [
            'editMode' => true,
            'request' => $abRequest,
            'form' => $form->createView(),
            'bookerInfo' => $abRequest->getBooker()->getBookerInfo(),
            'publicLink' => $publicLink,
        ]);
    }

    private function validReferer(Request $request)
    {
        $host = parse_url($request->server->get("HTTP_REFERER"), PHP_URL_HOST);
        $partner = $request->request->get('partner');

        return $host == $request->getHost() || $partner == 'pointimize';
    }

    private function populateFromJson(Request $request, AbRequest &$abRequest)
    {
        if (
            $request->getSession()->get('ref') != 147
            || !$request->isMethod('POST')
            || !$request->request->get('json')
            || $request->request->get('partner') != 'pointimize'
        ) {
            return;
        }

        $json = $request->request->get('json');

        $denormalizer = new AbRequestDenormalizer(
            new ClassMetadataFactory(new AnnotationLoader(new AnnotationReader())),
            new CamelCaseNameConverter(),
            null,
            null,
            null,
            null,
            [
                AbstractNormalizer::IGNORED_ATTRIBUTES => [
                    'AbCustomProgramID',
                    'AbPassengerID',
                    'RequestID',
                    'UserAgentID',
                ],
            ]
        );

        $serializer = new Serializer(
            [$denormalizer],
            [new JsonEncoder()]
        );

        try {
            $serializer->deserialize(
                $json,
                AbRequest::class,
                'json',
                [
                    'object_to_populate' => $abRequest,
                    'groups' => ['partner'],
                ]
            );
        } catch (\UnexpectedValueException $e) {
            $this->logger->error('AbRequest: Malformed json received from pointimize partner', ['json' => $json]);

            return;
        }

        if (!$abRequest->getUser()) {
            $user = new Usr();

            if ($abRequest->getContactName()) {
                [$first, $last] = explode(' ', $abRequest->getContactName());
                $user->setFirstname($first);
                $user->setLastname($last);
            }
            $user->setEmail($abRequest->getContactEmail());
            $user->setPhone1($abRequest->getContactPhone());
            $abRequest->setUser($user);
        }
    }

    private function catchErrors(AbRequest $request)
    {
        foreach ($request->getPassengers() as $passenger) {
            /** @var AbPassenger $passenger */
            if (!in_array($passenger->getGender(), ['M', 'F'])) {
                throw new \Exception('Invalid gender of the passenger');
            }
        }
    }

    private function shareAccounts(AbRequest $abRequest)
    {
        $this->programShareManager->bindAccounts($abRequest->getAccounts(), $abRequest->getBooker());

        return true;
    }

    private function getAbRequestForm(AbRequest $abRequest, $user)
    {
        return $this->createForm(AbRequestType::class, $abRequest, [
            'user' => $user,
            'booker' => $abRequest->getBooker(),
            'attr' => ['id' => 'booking_request_new'],
            'by_booker' => $abRequest->getByBooker(),
        ]);
    }
}
