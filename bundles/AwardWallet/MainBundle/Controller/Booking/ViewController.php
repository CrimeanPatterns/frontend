<?php

namespace AwardWallet\MainBundle\Controller\Booking;

use AwardWallet\MainBundle\Entity\AbAccountProgram;
use AwardWallet\MainBundle\Entity\AbCustomProgram;
use AwardWallet\MainBundle\Entity\AbMessage;
use AwardWallet\MainBundle\Entity\AbRequest;
use AwardWallet\MainBundle\Entity\AbShare;
use AwardWallet\MainBundle\Entity\Repositories\ProviderRepository;
use AwardWallet\MainBundle\Entity\Usr;
use AwardWallet\MainBundle\Form\Type\AbMessageType;
use AwardWallet\MainBundle\Form\Type\AbRequestPropertiesType;
use AwardWallet\MainBundle\FrameworkExtension\AwTokenStorageInterface;
use AwardWallet\MainBundle\Globals\AccountList\Resolver\ProgramStatusResolver;
use AwardWallet\MainBundle\Manager\BookingRequestManager;
use AwardWallet\MainBundle\Manager\Exception\EmptyPasswordException;
use AwardWallet\MainBundle\Manager\Exception\LocallyStoredPasswordException;
use AwardWallet\MainBundle\Manager\Exception\ProgramManagerRequiredException;
use AwardWallet\MainBundle\Manager\LogoManager;
use AwardWallet\MainBundle\Manager\ProgramShareManager;
use AwardWallet\MainBundle\Parameter\DefaultBookerParameter;
use AwardWallet\MainBundle\Security\AntiBruteforceLockerService;
use AwardWallet\MainBundle\Security\Reauthentication\Action;
use AwardWallet\MainBundle\Security\Reauthentication\ReauthenticatorWrapper;
use AwardWallet\MainBundle\Service\AppBot\Adapter\Slack;
use AwardWallet\MainBundle\Service\AppBot\AppBot;
use AwardWallet\MainBundle\Service\BusinessTransaction\BusinessTransactionManager;
use AwardWallet\MainBundle\Service\DateTimeInterval\Formatter as DateTimeIntervalFormatter;
use AwardWallet\MainBundle\Service\SocksMessaging\BookingMessaging;
use AwardWallet\MainBundle\Service\SocksMessaging\ClientInterface as SocksMessagingClientInterface;
use AwardWallet\MainBundle\Timeline\Manager;
use AwardWallet\WidgetBundle\Widget\BookingLeftMenuWidget;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\EntityRepository;
use Doctrine\Persistence\ManagerRegistry;
use Psr\Log\LoggerInterface;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\ParamConverter;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Security;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Form\Form;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Session\SessionInterface;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Routing\RouterInterface;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;
use Symfony\Contracts\Translation\TranslatorInterface;

/**
 * @Route("/awardBooking")
 */
class ViewController extends AbstractController
{
    private BookingRequestManager $bookingRequestManager;
    private AuthorizationCheckerInterface $authorizationChecker;
    private RouterInterface $router;
    private EntityManagerInterface $entityManager;
    private TranslatorInterface $translator;
    private ManagerRegistry $doctrine;
    private DateTimeIntervalFormatter $intervalFormatter;
    private AwTokenStorageInterface $tokenStorage;

    public function __construct(
        BookingRequestManager $bookingRequestManager,
        AuthorizationCheckerInterface $authorizationChecker,
        RouterInterface $router,
        EntityManagerInterface $entityManager,
        TranslatorInterface $translator,
        ManagerRegistry $doctrine,
        DateTimeIntervalFormatter $intervalFormatter,
        AwTokenStorageInterface $tokenStorage
    ) {
        $this->bookingRequestManager = $bookingRequestManager;
        $this->authorizationChecker = $authorizationChecker;
        $this->router = $router;
        $this->entityManager = $entityManager;
        $this->translator = $translator;
        $this->doctrine = $doctrine;
        $this->intervalFormatter = $intervalFormatter;
        $this->tokenStorage = $tokenStorage;
    }

    /**
     * @Route("/view/{id}", name="aw_booking_view_index", options={"expose"=true}, defaults={"id" = "0"}, requirements={"id" = "\d+"})
     * @ParamConverter("abRequest", class="AwardWalletMainBundle:AbRequest")
     * @Template("@AwardWalletMain/Booking/View/index.html.twig")
     */
    public function indexAction(
        AbRequest $abRequest,
        Request $request,
        Manager $timelineManager,
        ProgramStatusResolver $programStatusResolver,
        BookingLeftMenuWidget $bookingLeftMenu,
        LogoManager $logoManager,
        DefaultBookerParameter $defaultBookerParameter,
        SessionInterface $session,
        SocksMessagingClientInterface $socksMessagingClient,
        BookingMessaging $bookingMessaging,
        $vapidPublicKey,
        $webpushIdParam
    ) {
        if ($request->query->has('conf') && $abRequest->getConfirmationCode() === $request->query->get('conf')) {
            $this->bookingRequestManager->confirmContactEmail($abRequest);
        }

        if ($this->authorizationChecker->isGranted('SITE_MOBILE_VERSION_SUITABLE')) {
            return new RedirectResponse(sprintf('/m/booking/%d/details', $abRequest->getAbRequestID()));
        }

        if (!$this->authorizationChecker->isGranted('VIEW', $abRequest)) {
            throw $this->createAccessDeniedException();
        }

        $logoManager->setBookingRequest($abRequest);
        $bookingLeftMenu->setActiveItem($abRequest->isActive() ? "active" : "archive");
        $propertiesForm = $this->createForm(AbRequestPropertiesType::class, $abRequest);

        $message = new AbMessage();

        if ($request->attributes->has('post')) { // from MessageController::addAction, failed post, redisplay from
            $message->setPost($request->attributes->get('post'));
        }

        if ($request->query->has('post')) {
            $message->setPost($request->query->get('post'));
        }
        $messageForm = $this->createForm(AbMessageType::class, $message, ['request' => $abRequest]);

        $messageSent = $request->query->get('_');

        $readed = $this->bookingRequestManager->getMarkRead($abRequest, $this->tokenStorage->getToken()->getUser());
        $this->bookingRequestManager->markAsRead($abRequest, $this->tokenStorage->getToken()->getUser(), new \DateTime());

        $this->bookingRequestManager->flush();

        // get booker
        if ($abRequest->getBooker() instanceof Usr && $abRequest->getBooker()->isBooker()) {
            $abBookerInfo = $abRequest->getBooker()->getBookerInfo();
        } else {
            $abBookerInfo = $this->getDoctrine()
                ->getRepository(\AwardWallet\MainBundle\Entity\AbBookerInfo::class)
                ->findOneBy(['UserID' => $defaultBookerParameter->get()]);
        }

        $otherRequests = $this->getDoctrine()->getRepository(\AwardWallet\MainBundle\Entity\AbRequest::class)->getOtherRequests($abRequest);

        $firstView = false;

        if (!$abRequest->getByBooker()) {
            $firstView = !empty($session->getFlashBag()->get('new_booking_request'));
        }

        return [
            'request' => $abRequest,
            'messaging' => json_encode($socksMessagingClient->getClientData()),
            'channels' => json_encode($bookingMessaging->getChannels($abRequest, $this->tokenStorage->getToken()->getUser())),
            'currentUser' => $this->tokenStorage->getToken()->getUser(),
            'reqRep' => $this->getDoctrine()->getRepository(\AwardWallet\MainBundle\Entity\AbRequest::class),
            'usrRep' => $this->getDoctrine()->getRepository(\AwardWallet\MainBundle\Entity\Usr::class),
            'travelplansRep' => $this->getDoctrine()->getRepository(\AwardWallet\MainBundle\Entity\Travelplan::class),
            'agentsRep' => $this->getDoctrine()->getRepository(\AwardWallet\MainBundle\Entity\Useragent::class),
            'timelineManager' => $timelineManager,
            'providers' => $this->getDoctrine()->getRepository(\AwardWallet\MainBundle\Entity\Provider::class)->findBookingProgs(''),
            'statusResolver' => $programStatusResolver,
            'authorizationChecker' => $this->authorizationChecker,
            'propertiesForm' => $propertiesForm->createView(),
            'messageForm' => $messageForm->createView(),
            'bookerInfo' => $abBookerInfo,
            'readed' => $readed,
            'otherRequests' => $otherRequests,
            'messageSent' => $messageSent,
            'firstView' => $firstView,
            'isNdrContactEmail' => $this->getDoctrine()->getRepository(\AwardWallet\MainBundle\Entity\Emailndr::class)->isNdr($abRequest->getMainContactEmail()),
            'vapid_public_key' => $vapidPublicKey,
            'webpush_id' => $webpushIdParam,
            'clientInfo' => $this->getClientInfo($abRequest),
        ];
    }

    /**
     * @Route("/resend/{id}", name="aw_booking_view_resend_email", defaults={"id" = "0"}, requirements={"id" = "\d+"}, options={"expose" = true})
     * @Security("is_granted('VIEW', abRequest)")
     * @ParamConverter("abRequest", class="AwardWalletMainBundle:AbRequest")
     */
    public function resendEmail(Request $request, AbRequest $abRequest)
    {
        $this->bookingRequestManager->sendEmailOnNewRequest($abRequest);

        return $this->redirect(
            $this->router->generate('aw_booking_view_index', ['id' => $abRequest->getAbRequestID()])
        );
    }

    /**
     * @Security("is_granted('USER_BOOKING_REFERRAL') and is_granted('CSRF') and is_granted('VIEW', abRequest)")
     * @Route("/properties_ajax/{id}", name="aw_booking_view_ajaxproperties", methods={"POST"}, defaults={"id" = "0"}, requirements={"id" = "\d+"}, options={"expose" = true})
     * @ParamConverter("abRequest", class="AwardWalletMainBundle:AbRequest")
     */
    public function ajaxPropertiesAction(Request $request, AbRequest $abRequest, BusinessTransactionManager $transactionManager)
    {
        $propertiesForm = $this->createForm(AbRequestPropertiesType::class, $abRequest);

        $userRep = $this->getDoctrine()->getRepository(\AwardWallet\MainBundle\Entity\Usr::class);
        $internalStatusRep = $this->getDoctrine()->getRepository(\AwardWallet\MainBundle\Entity\AbRequestStatus::class);

        $currentStatus = $abRequest->getStatus();
        $realCurrentStatus = $abRequest->getRealStatus(false);

        $propertiesForm->handleRequest($request);

        if ($propertiesForm->isSubmitted()) {
            if ($propertiesForm->isValid()) {
                /** @var \AwardWallet\MainBundle\Entity\AbRequest $AbRequest */
                $AbRequest = $propertiesForm->getData();

                if ($propertiesForm->get('Assigned')->getData()) {
                    if ($assigned = $userRep->find($propertiesForm->get('Assigned')->getData())) {
                        $AbRequest->setAssignedUser($assigned);
                    }
                } else {
                    $AbRequest->setAssignedUser(null);
                }

                if ($propertiesForm->has('InternalStatus') && $propertiesForm->get('InternalStatus')->getData()) {
                    if ($internalStatus = $internalStatusRep->find($propertiesForm->get('InternalStatus')->getData())) {
                        $AbRequest->setInternalStatus($internalStatus);
                    }
                } else {
                    $AbRequest->setInternalStatus(null);
                }

                $changedStatus = $AbRequest->getStatus() != $currentStatus;
                $realChangedStatus = $AbRequest->getRealStatus(false) != $realCurrentStatus;

                if ($AbRequest->getStatus() == AbRequest::BOOKING_STATUS_FUTURE) {
                    if ($changedStatus || $abRequest->getRemindDate() != $propertiesForm->get('UntilDate')->getData()) {
                        $AbRequest->setRemindDate($propertiesForm->get('UntilDate')->getData());
                    }
                }

                if ($realChangedStatus) {
                    $this->bookingRequestManager->changeStatus($AbRequest, $AbRequest->getStatus());
                }

                if ($propertiesForm->get('MarkAsUnread')->getData()) {
                    $this->bookingRequestManager->markAsUnread($AbRequest, $this->tokenStorage->getToken()->getUser());
                }

                if ($AbRequest->getStatus() != AbRequest::BOOKING_STATUS_FUTURE) {
                    $AbRequest->setRemindDate(null);
                }
                $abRequest->setLastUpdateDate(new \DateTime());

                $this->bookingRequestManager->flush();

                if ($changedStatus && $AbRequest->getStatus() == AbRequest::BOOKING_STATUS_PROCESSING) {
                    if (!$transactionManager->isBookingRequestPaid($AbRequest)) {
                        $transactionManager->bookingRequestComplete($AbRequest);
                    }
                }

                $response = [
                    'success' => true,
                ];

                return new JsonResponse($response);
            } else {
                $response = [
                    'success' => false,
                    'errors' => [],
                ];

                /** @var Form $field */
                foreach ($propertiesForm as $field) {
                    $e = [];

                    foreach ($field->getErrors() as $error) {
                        $e[] = $error->getMessage();
                    }

                    if ($e) {
                        $response['errors'][$field->getName()] = implode('<br>', $e);
                    }
                }

                return new JsonResponse($response);
            }
        }

        throw $this->createNotFoundException();
    }

    /**
     * @Route("/cancel/{id}", name="aw_booking_view_cancel", methods={"GET", "POST"}, requirements={"id" = "\d+"})
     * @ParamConverter("abRequest", class="AwardWalletMainBundle:AbRequest")
     * @Security("is_granted('CSRF') and is_granted('CANCEL', abRequest)")
     * @return \Symfony\Component\HttpFoundation\RedirectResponse|\Symfony\Component\HttpFoundation\Response
     */
    public function cancelAction(Request $request, AbRequest $abRequest)
    {
        // for backTo from login
        if ($request->getMethod() != 'POST') {
            return $this->redirect('/');
        }

        $this->bookingRequestManager->changeStatus($abRequest, $abRequest::BOOKING_STATUS_CANCELED);
        $this->bookingRequestManager->flush();

        return $this->redirect($this->router->generate('aw_booking_view_index', ['id' => $abRequest->getAbRequestID()]));
    }

    /**
     * @Route("/repost/{id}", name="aw_booking_view_repost", methods={"GET", "POST"}, requirements={"id" = "\d+"})
     * @ParamConverter("abRequest", class="AwardWalletMainBundle:AbRequest")
     * @Security("is_granted('CSRF') and is_granted('REPOST', abRequest)")
     * @return \Symfony\Component\HttpFoundation\RedirectResponse|\Symfony\Component\HttpFoundation\Response
     */
    public function repostAction(Request $request, AbRequest $abRequest)
    {
        // for backTo from login
        if ($request->getMethod() != 'POST') {
            return $this->redirect('/');
        }

        $this->bookingRequestManager->changeStatus($abRequest, $abRequest::BOOKING_STATUS_PENDING);
        $this->bookingRequestManager->flush();

        return $this->redirect($this->router->generate('aw_booking_view_index', ['id' => $abRequest->getAbRequestID()]));
    }

    /**
     * Mark request read.
     *
     * @Security("is_granted('CSRF') and is_granted('VIEW', abRequest)")
     * @Route("/markRead/{id}", name="aw_booking_view_markread", requirements={"id" = "\d+"})
     * @ParamConverter("request", class="AwardWalletMainBundle:AbRequest")
     */
    public function markReadAction(Request $httpRequest, AbRequest $abRequest)
    {
        $user = $this->tokenStorage->getToken()->getUser();
        $readed = $httpRequest->query->get('readed');

        if ($readed === 'true') {
            $this->bookingRequestManager->markAsRead($abRequest, $user, new \DateTime());
        } else {
            $this->bookingRequestManager->markAsUnread($abRequest, $user);
        }

        return new JsonResponse('success');
    }

    /**
     * Clarify custom programs in request.
     *
     * @Security("is_granted('CSRF') and is_granted('VIEW', abRequest) and is_granted('BOOKER', abRequest)")
     * @Route("/clarify/{id}", name="aw_booking_view_clarify", requirements={"id" = "\d+"}, options={"expose"=true})
     * @ParamConverter("abRequest", class="AwardWalletMainBundle:AbRequest")
     */
    public function clarifyAction(
        Request $request,
        AbRequest $abRequest,
        ProgramStatusResolver $programStatusResolver,
        LoggerInterface $logger
    ) {
        $accounts = $request->request->get('accounts');
        /** @var ProviderRepository $providers */
        $providers = $this->entityManager->getRepository(\AwardWallet\MainBundle\Entity\Provider::class);
        /** @var EntityRepository $customs */
        $customs = $this->entityManager->getRepository(\AwardWallet\MainBundle\Entity\AbCustomProgram::class);
        /** @var EntityRepository $programs */
        $programs = $this->entityManager->getRepository(\AwardWallet\MainBundle\Entity\AbAccountProgram::class);

        $requested = [];

        if (is_array($accounts)) {
            foreach ($accounts as $account) {
                $logger->debug("saving account", $account);

                if (!empty($account['providerId'])) {
                    $provider = $providers->find($account['providerId']);

                    if (!empty($account['rowId'])) {
                        /** @var AbCustomProgram $custom */
                        $custom = $customs->find($account['rowId']);

                        if ($custom->getRequest() != $abRequest) {
                            throw new AccessDeniedException("Access denied to custom program " . $account['rowId']);
                        }
                    } else {
                        // new record
                        $custom = new AbCustomProgram();
                        $custom->setName($provider->getDisplayname());
                        $custom->setRequest($abRequest);
                    }
                    $custom->setProvider($provider);
                    $this->entityManager->persist($custom);
                    $requested[] = $custom;
                } else {
                    if (!empty($account['rowId'])) {
                        $finded = $programs->findBy(['RequestID' => $abRequest->getAbRequestID(), 'AccountID' => $account['rowId']]);

                        if (count($finded)) {
                            /** @var AbAccountProgram $program */
                            $program = $finded[0];
                            $requested[] = $program;
                        } else {
                            /** @var AbCustomProgram $custom */
                            $custom = $customs->find($account['rowId']);

                            if ($custom) {
                                $requested[] = $custom;
                            }
                        }
                    }
                }
            }
            $this->entityManager->flush();

            $this->bookingRequestManager->requestSharing($this->getUser(), $abRequest, $requested);
        }

        return $this->render('@AwardWalletMain/Booking/View/lp-table.html.twig', [
            'usrRep' => $this->getDoctrine()->getRepository(\AwardWallet\MainBundle\Entity\Usr::class),
            'agentsRep' => $this->getDoctrine()->getRepository(\AwardWallet\MainBundle\Entity\Useragent::class),
            'statusResolver' => $programStatusResolver,
            'authorizationChecker' => $this->authorizationChecker,
            'travelplansRep' => $this->getDoctrine()->getRepository(\AwardWallet\MainBundle\Entity\Travelplan::class),
            'request' => $abRequest,
        ]);
    }

    /**
     * Convert custom program in request.
     *
     * @Security("is_granted('CSRF') and is_granted('VIEW', abRequest)")
     * @Route("/reveal_password_ajax/{id}/{accountID}", name="aw_booking_view_revealpassword", requirements={"id" = "\d+", "accountID" = "\d+"}, options={"expose"=true})
     * @ParamConverter("abRequest", class="AwardWalletMainBundle:AbRequest")
     */
    public function revealPasswordAction(
        AbRequest $abRequest,
        $accountID,
        Request $request,
        ReauthenticatorWrapper $reauthenticator,
        ProgramShareManager $programShareManager
    ) {
        $action = Action::getRevealAccountPasswordAction($accountID);

        if (!$reauthenticator->isReauthenticated($action)) {
            return new JsonResponse(['success' => false]);
        }

        $reauthenticator->reset($action);
        $accountsRep = $this->entityManager->getRepository(\AwardWallet\MainBundle\Entity\Account::class);
        $account = $accountsRep->find($accountID);

        $programShareManager->setUser($this->tokenStorage->getBusinessUser());
        $password = '';

        try {
            $password = $programShareManager->revealPassword($account); // access right will be checked here
            $message = "<p>In order to copy <strong>" . $account->getOwnerFullName() . "'s " . $account->getProviderid()->getProgramname() . "</strong> password to clipboard please click the button below.</p>";
        } catch (ProgramManagerRequiredException $e) {
            $message = "<p>Password was not copied to clipboard as you don't have sufficient permissions to access this password.</p>";
        } catch (LocallyStoredPasswordException $e) {
            $message = "<p><strong>" . $account->getOwnerFullName() . "'s " . $account->getProviderid()->getProgramname() . "</strong> password was not copied to clipboard as he or she saved this password locally on his or her computer. Please instruct that user to save the password in the AwardWallet's database for this feature to work.</p>";
        } catch (EmptyPasswordException $e) {
            $message = "<p><strong>" . $account->getOwnerFullName() . "'s " . $account->getProviderid()->getProgramname() . "</strong> password was not copied to clipboard as password is empty.</p>";
        }
        $response = [
            'success' => true,
            'message' => $message,
            'password' => $password,
        ];

        return new JsonResponse($response);
    }

    /**
     * Gain full control of the personal account.
     *
     * @Security("is_granted('USER_BOOKING_AW') and is_granted('CSRF') and is_granted('VIEW', abRequest)")
     * @Route("/gainFull/{id}", name="aw_booking_view_gainfull", requirements={"id" = "\d+"})
     * @ParamConverter("abRequest", class="AwardWalletMainBundle:AbRequest")
     */
    public function gainFullControlAction(
        AbRequest $abRequest,
        Request $request,
        AppBot $appBot,
        AntiBruteforceLockerService $lockerService,
        $requiresChannel,
        $host
    ) {
        $error = $lockerService->checkForLockout($request->getClientIp());

        if (!empty($error)) {
            return new JsonResponse([
                'success' => false,
                'error' => $error,
            ]);
        }

        $booker = $this->tokenStorage->getBusinessUser();
        $em = $this->getDoctrine()->getManager();
        $notApproved = $em->getRepository(\AwardWallet\MainBundle\Entity\AbShare::class)
            ->findOneBy([
                'user' => $abRequest->getUser(),
                'booker' => $booker,
                'isApproved' => false,
            ]);

        if (is_null($notApproved)) {
            $em->persist(
                new AbShare($abRequest->getUser(), $booker, false)
            );
            $em->flush();

            // send to slack
            $message = 'New Booking Sharing Request - '
                . $requiresChannel
                . '://' . $host
                . $this->generateUrl('abshare_list');
            $appBot->send(Slack::CHANNEL_AW_SYSADMIN, $message);
        }

        return new JsonResponse([
            'success' => true,
        ]);
    }

    private function getClientInfo(AbRequest $abRequest): array
    {
        $abRequestRep = $this->getDoctrine()->getRepository(\AwardWallet\MainBundle\Entity\AbRequest::class);
        $usrRep = $this->getDoctrine()->getRepository(\AwardWallet\MainBundle\Entity\Usr::class);

        // Account Age
        $accountAge = $this->intervalFormatter
            ->formatDuration(
                new \DateTime(),
                $abRequest->getUser()->getCreationdatetime(),
                false,
                false,
                true
            );

        // Past Booking Requests
        $allUserRequests = $this->doctrine->getManager()->createQuery('SELECT r FROM AwardWallet\MainBundle\Entity\AbRequest r WHERE r.User = :user')->setParameter('user', $abRequest->getUser())->getResult();
        $pastBookingsStatuses = [];

        foreach ($allUserRequests as $request) {
            if (!array_key_exists($request->getStatus(), $pastBookingsStatuses)) {
                $pastBookingsStatuses[$request->getStatus()] = 0;
            }
            ++$pastBookingsStatuses[$request->getStatus()];
        }
        $pastBookings = [];

        foreach ($pastBookingsStatuses as $status => $statusCount) {
            $pastBookings[] = $statusCount . ' ' . $this->translator->trans($abRequestRep->getStatusDescription($status), [], 'booking');
        }

        // Paid for AW Plus
        $paymentStats = $usrRep->getPaymentStatsByUser($abRequest->getUser()->getUserid());

        if (0 === (int) $paymentStats['PaidOrders']) {
            $paidsCountInfo = $this->translator->trans('account.background-updating-never');
        } else {
            $paidsCountInfo = $this->translator->trans('number-times', ['%count%' => $paymentStats['PaidOrders']], 'booking');
        }

        return [
            'accountAge' => $accountAge,
            'accountsCount' => $abRequest->getUser()->getAccounts(),
            'pastBookingRequests' => implode(', ', $pastBookings),
            'bookingCount' => array_sum($pastBookingsStatuses),
            'paidAwPlus' => $paidsCountInfo,
            'timesLoggedIn' => $abRequest->getUser()->getLogoncount(),
        ];
    }
}
