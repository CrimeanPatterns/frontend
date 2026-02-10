<?php

namespace AwardWallet\MainBundle\Manager;

use AwardWallet\MainBundle\Entity\Account;
use AwardWallet\MainBundle\Entity\Accountshare;
use AwardWallet\MainBundle\Entity\BusinessInfo;
use AwardWallet\MainBundle\Entity\CartItem\AwPlusTrial;
use AwardWallet\MainBundle\Entity\CartItem\AwPlusTrial6Months;
use AwardWallet\MainBundle\Entity\Country;
use AwardWallet\MainBundle\Entity\Coupon;
use AwardWallet\MainBundle\Entity\Invitecode;
use AwardWallet\MainBundle\Entity\Invites;
use AwardWallet\MainBundle\Entity\MobileDevice;
use AwardWallet\MainBundle\Entity\Repositories\OwnerRepository;
use AwardWallet\MainBundle\Entity\Repositories\UseragentRepository;
use AwardWallet\MainBundle\Entity\Sitead;
use AwardWallet\MainBundle\Entity\TimelineShare;
use AwardWallet\MainBundle\Entity\Useragent;
use AwardWallet\MainBundle\Entity\Usr;
use AwardWallet\MainBundle\Form\Model\Profile\NotificationModel;
use AwardWallet\MainBundle\FrameworkExtension\Listeners\ReferalListener;
use AwardWallet\MainBundle\FrameworkExtension\Mailer\Mailer;
use AwardWallet\MainBundle\FrameworkExtension\Mailer\Template\User\EmailVerification;
use AwardWallet\MainBundle\FrameworkExtension\Mailer\Template\User\FreeUpgrade;
use AwardWallet\MainBundle\FrameworkExtension\Mailer\Template\User\PasswordChanged;
use AwardWallet\MainBundle\FrameworkExtension\Mailer\Template\User\ResetPassword;
use AwardWallet\MainBundle\FrameworkExtension\Mailer\Template\User\UserRegisteredPerRequest;
use AwardWallet\MainBundle\FrameworkExtension\Mailer\Template\User\WelcomeToAw;
use AwardWallet\MainBundle\FrameworkExtension\Mailer\Template\User\WelcomeToAwUsAccountList;
use AwardWallet\MainBundle\FrameworkExtension\Mailer\Template\User\WelcomeToAwUsMailbox;
use AwardWallet\MainBundle\FrameworkExtension\Mailer\Template\User\WelcomeToAwUsTimeline;
use AwardWallet\MainBundle\Globals\Cart\Manager;
use AwardWallet\MainBundle\Globals\Image\AvatarCreator;
use AwardWallet\MainBundle\Globals\LoggerContext\ContextAwareLoggerWrapper;
use AwardWallet\MainBundle\Globals\StringHandler;
use AwardWallet\MainBundle\Globals\Utils\BinaryLogger\BinaryLoggerFactory;
use AwardWallet\MainBundle\Manager\Exception\NotBusinessAdministratorException;
use AwardWallet\MainBundle\Security\AntiBruteforceLockerService;
use AwardWallet\MainBundle\Security\LoginGenerator;
use AwardWallet\MainBundle\Security\RememberMe\RememberMeServices;
use AwardWallet\MainBundle\Security\RememberMe\RememberMeTokenProvider;
use AwardWallet\MainBundle\Security\SessionListener;
use AwardWallet\MainBundle\Security\TwoFactorAuthentication\TwoFactorAuthenticationService;
use AwardWallet\MainBundle\Security\Utils;
use AwardWallet\MainBundle\Service\AppBot\Adapter\Slack;
use AwardWallet\MainBundle\Service\AppBot\AppBot;
use AwardWallet\MainBundle\Service\GeoLocation\GeoLocation;
use AwardWallet\MainBundle\Service\MxRecordChecker;
use AwardWallet\MainBundle\Service\TaskScheduler\Producer;
use AwardWallet\MainBundle\Service\UsGreeting\EmailTask;
use AwardWallet\MainBundle\Timeline\Manager as TimelineManager;
use Doctrine\Common\Collections\Criteria;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\ORMInvalidArgumentException;
use Psr\Log\LoggerInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Session\Session;
use Symfony\Component\HttpFoundation\Session\SessionInterface;
use Symfony\Component\HttpFoundation\Session\Storage\MockArraySessionStorage;
use Symfony\Component\HttpKernel\Event\FilterResponseEvent;
use Symfony\Component\HttpKernel\Event\ResponseEvent;
use Symfony\Component\HttpKernel\KernelEvents;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Routing\RouterInterface;
use Symfony\Component\Security\Core\Authentication\Token\RememberMeToken;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use Symfony\Component\Security\Core\Authentication\Token\SwitchUserToken;
use Symfony\Component\Security\Core\Encoder\EncoderFactoryInterface;
use Symfony\Component\Security\Core\Exception\AuthenticationException;
use Symfony\Component\Security\Guard\Token\PostAuthenticationGuardToken;
use Symfony\Component\Security\Http\Event\InteractiveLoginEvent;
use Symfony\Component\Security\Http\Event\SwitchUserEvent;
use Symfony\Component\Security\Http\SecurityEvents;
use Symfony\Contracts\Translation\TranslatorInterface;

class UserManager
{
    public const KEY_KIND_JSON = 1;
    public const KEY_KIND_IMPERSONATE = 2;

    public const KEY_ACCESS_BALANCE = 1;
    public const KEY_ACCESS_PASSWORD = 2;

    public const KEY_STATE_ENABLED = 1;
    public const KEY_STATE_REVOKED = 2;

    public const LOGIN_TYPE_USER = 1;
    public const LOGIN_TYPE_IMPERSONATE = 2;
    public const LOGIN_TYPE_IMPERSONATE_FULLY = 3;
    public const LOGIN_TYPE_ADMINISTRATIVE = 4;

    public const SESSION_KEY_AUTHORIZE_SUCCESS_URL = 'authorizeSuccessUrl';
    public const SESSION_KEY_REFERRAL = 'userReferral';

    private $em;
    private $tokenStorage;
    private $eventDispatcher;
    private $encoderFactory;
    private $rememberServices;
    private $rememberKey;
    private $router;
    private $logger;
    private $translator;
    private $session;
    private $container;
    /**
     * @var AntiBruteforceLockerService
     */
    private $forgotLocker;
    /**
     * @var RememberMeTokenProvider
     */
    private $tokenProvider;
    /**
     * @var SessionListener
     */
    private $sessionListener;
    /**
     * @var LoggerInterface
     */
    private $securityLogger;

    /**
     * @var TimelineManager
     */
    private $timelineManager;

    /**
     * @var AccountManager
     */
    private $accountManager;

    /**
     * @var LoginGenerator
     */
    private $loginGenerator;

    /** @var AppBot */
    private $appBot;
    private MobileDeviceManager $mobileDeviceManager;
    private BinaryLoggerFactory $check;

    private GeoLocation $geoLocation;
    private MxRecordChecker $mxRecordChecker;

    public function __construct(
        EntityManagerInterface $em,
        TokenStorageInterface $tokenStorage,
        EventDispatcherInterface $dispatcher,
        EncoderFactoryInterface $encoderFactory,
        RememberMeServices $rememberMeServices,
        $rememberKey,
        RouterInterface $router,
        LoggerInterface $logger,
        TranslatorInterface $translator,
        SessionInterface $session,
        ContainerInterface $container,
        AntiBruteforceLockerService $forgotLocker,
        RememberMeTokenProvider $tokenProvider,
        SessionListener $sessionListener,
        LoggerInterface $securityLogger,
        TimelineManager $timelineManager,
        AccountManager $accountManager,
        LoginGenerator $loginGenerator,
        AppBot $appBot,
        MobileDeviceManager $mobileDeviceManager,
        GeoLocation $geoLocation,
        MxRecordChecker $mxRecordChecker
    ) {
        $this->em = $em;
        $this->tokenStorage = $tokenStorage;
        $this->eventDispatcher = $dispatcher;
        $this->encoderFactory = $encoderFactory;
        $this->rememberServices = $rememberMeServices;
        $this->rememberKey = $rememberKey;
        $this->router = $router;
        $this->logger =
            (new ContextAwareLoggerWrapper($logger))
            ->setMessagePrefix('user manager: ');
        $this->translator = $translator;
        $this->session = $session;
        $this->container = $container;
        $this->forgotLocker = $forgotLocker;
        $this->tokenProvider = $tokenProvider;
        $this->sessionListener = $sessionListener;
        $this->securityLogger = $securityLogger;
        $this->timelineManager = $timelineManager;
        $this->accountManager = $accountManager;
        $this->loginGenerator = $loginGenerator;
        $this->appBot = $appBot;
        $this->mobileDeviceManager = $mobileDeviceManager;
        $this->check = (new BinaryLoggerFactory($this->logger))->toInfo();
        $this->geoLocation = $geoLocation;
        $this->mxRecordChecker = $mxRecordChecker;
    }

    public function loadToken(Usr $user, $savePassword, $loginType = self::LOGIN_TYPE_USER, $forceAwPlus = false, $originalToken = null)
    {
        $oldUserId = null;
        $oldToken = $this->tokenStorage->getToken();

        if ($oldToken !== null && $oldToken->getUser() !== null && $oldToken->getUser() instanceof Usr) {
            $oldUserId = $oldToken->getUser()->getUserid();
        }

        // are we already logged in as this user?
        if ($user->isBusiness()) {
            throw new \Exception('could not login business user');
        }

        $authChecker = $this->container->get('security.authorization_checker');
        $request = $this->container->get('request_stack')->getMasterRequest();

        // !empty here to allow util/sync/updateProvideInfo run without firewall
        if (!empty($request) && $authChecker->isGranted('SITE_BUSINESS_AREA')) {
            $ur = $this->em->getRepository(Usr::class);
            $business = $ur->getBusinessByUser($user, [ACCESS_ADMIN, ACCESS_BOOKING_MANAGER, ACCESS_BOOKING_VIEW_ONLY]);

            if (empty($business)) {
                throw new NotBusinessAdministratorException();
            }
        }

        $currentDevice = $this->mobileDeviceManager->getCurrentDevice();

        if ($savePassword) {
            if ($loginType != self::LOGIN_TYPE_USER) {
                throw new \InvalidArgumentException('impersonate is not compatible with savePassword');
            }
            $token = new RememberMeToken($user, 'secured_area', $this->rememberKey);
            // add remember-me cookie to response
            $this->eventDispatcher->addListener(KernelEvents::RESPONSE, function (FilterResponseEvent $event) use ($token, $currentDevice, $user) {
                if ($event->isMasterRequest() && !$event->getResponse()->isServerError()) {
                    $request = $event->getRequest();
                    $this->rememberServices->onLoginSuccess($request, $event->getResponse(), $token);

                    if (
                        $this->check->that('current device')->wasNot('found before new token loaded')
                            ->on(!$currentDevice)
                        && $this->check->that('device id request attribute')->was('set')
                            ->on($request->attributes->has(MobileDeviceManager::REQUEST_ATTRIBUTE_DEVICE_ID))
                    ) {
                        $currentDevice = $this->check->that('device')->was('found by request attribute')
                            ->on($this->em->getRepository(MobileDevice::class)->find($request->attributes->get(MobileDeviceManager::REQUEST_ATTRIBUTE_DEVICE_ID)));
                    }

                    if ($currentDevice && $this->session) {
                        $this->mobileDeviceManager->updateRememberMeTokenBySession($user->getUserid(), $currentDevice, $this->session->getId());
                    }
                }
            }, 0);
        } else {
            $roles = $user->getRoles();

            if (in_array($loginType, [self::LOGIN_TYPE_IMPERSONATE, self::LOGIN_TYPE_IMPERSONATE_FULLY])) {
                if (empty($originalToken)) {
                    $originalToken = $this->tokenStorage->getToken();
                }

                /** @var Usr $originalUser */
                $originalUser = $originalToken->getUser();

                $roles[] = $loginType == self::LOGIN_TYPE_IMPERSONATE ? 'ROLE_IMPERSONATED' : 'ROLE_IMPERSONATED_FULLY';
                $roles[] = 'ROLE_IMPERSONATED_ANY';

                if ($forceAwPlus) {
                    $roles[] = 'ROLE_AWPLUS';
                }

                $request = $this->container->get('request_stack')->getMasterRequest();
                $this->em->getConnection()->executeQuery(
                    'insert into ImpersonateLog(UserID, TargetUserID, CreationDate, IPAddress, UserAgent)
                    values(:userId, :targetUserId, now(), :ip, :agent)',
                    [
                        'userId' => $originalUser->getId(),
                        'targetUserId' => $user->getId(),
                        'ip' => $request->getClientIp(),
                        'agent' => $request->headers->get('user-agent'),
                    ]
                );
                $this->logger->warning('impersonate started', [
                    'TargetUserID' => $user->getId(),
                    'IP' => $request->getClientIp(),
                    'UserAgent' => $request->headers->get('user-agent'),
                    'CountryID' => $user->getCountryid()]
                );

                $token = new SwitchUserToken($user, $user->getPassword(), 'secured_area', $roles, $originalToken);
            } else {
                $token = new PostAuthenticationGuardToken($user, 'secured_area', $roles);
            }
        }

        if ($loginType == self::LOGIN_TYPE_USER) {
            $this->eventDispatcher->addListener(KernelEvents::RESPONSE, function (FilterResponseEvent $event) use ($user) {
                $response = $event->getResponse();

                if ($event->isMasterRequest() && !$response->isServerError()) {
                    $twoFactor = $this->container->get(TwoFactorAuthenticationService::class);
                    $request = $this->container->get('request_stack')->getMasterRequest();
                    $twoFactor->addAuthKeyCookie($request, $response, $user);
                }
            }, 0);
        }

        $this->tokenStorage->setToken($token);

        if (php_sapi_name() != 'cli' || !empty(ob_get_status())) { // we can't start session in console without output buffering
            $this->container->get('security.csrf.token_manager')->refreshToken('');
            ResetFormToken();
        }

        $request = $this->container->get('request_stack')->getMasterRequest();

        if (empty($request)) {
            $request = new Request([], [], [], [], [], ['REMOTE_ADDR' => '127.0.0.1']);
            $request->setSession(new Session(new MockArraySessionStorage()));
        } // cli
        else {
            // do not regenerate session id if user was not changed (password change)
            // otherwise other session invalidation will fail (at least test SessionCest.php:testOtherSessionsInvalidationAfterPasswordChange)
            if ($oldUserId !== $user->getId()) {
                $request->getSession()->migrate(true);
            }
        }

        if ($loginType != self::LOGIN_TYPE_USER) {
            $this->eventDispatcher->dispatch(new SwitchUserEvent($request, $token->getUser()), SecurityEvents::SWITCH_USER);
        } else {
            $this->eventDispatcher->dispatch(new InteractiveLoginEvent($request, $token), SecurityEvents::INTERACTIVE_LOGIN);
        }

        if ($currentDevice && $this->session) {
            $this->mobileDeviceManager->updateRememberMeTokenBySession($user->getId(), $currentDevice, $this->session->getId());
        }
    }

    /**
     * refresh token after password change.
     */
    public function refreshToken()
    {
        $token = $this->tokenStorage->getToken();
        $savePassword = $token instanceof RememberMeToken;
        $user = $token->getUser();

        if (!$user instanceof Usr) {
            return;
        }

        $this->em->refresh($user);
        $this->loadToken($user, $savePassword);
    }

    public function registerUser(Usr $user, Request $request, $giveTrial = true, $business = false, bool $sendEmail = true)
    {
        $sendEmail = $sendEmail && !$business;
        $ref = $request->getSession()->get(ReferalListener::SESSION_REF_KEY);

        if (SITE_BRAND == SITE_BRAND_CWT) {
            $ref = 123;
            // disable email notifications by default for CWT users
            $user->setCheckinreminder(0);
        }

        if ($ref) {
            $siteAd = $this->em->getRepository(Sitead::class)->find($ref);

            if ($siteAd) {
                $this->securityLogger->info('set CameFrom to ' . $ref, ['UserID' => $user->getUserid()]);
                $user->setCamefrom($siteAd->getSiteadid());
                $user->setSiteAd($siteAd);

                if ($siteAd->getBooker()) {
                    $user->setOwnedByBusiness($siteAd->getBooker());
                }

                $siteAd
                    ->setRegisters($siteAd->getRegisters() + 1)
                    ->setLastregister(new \DateTime());
            }
        }

        $referer = $request->getSession()->get(ReferalListener::REFERER_SESSION_KEY);

        if (!empty($referer)) {
            $this->logger->info('setReferer UserManager', [
                'referer' => $referer,
                'ip' => $request->getClientIp(),
            ]);
            $user->setReferer(substr($referer, 0, 250));
        }

        if ($business) {
            $user->setPass('disabled');
            $user->setFirstname('Business');
            $user->setLastname('Account');
            $user->setEmail('b.' . $business . '@awardwallet.com');
            $user->setLogin($this->em->getRepository(Usr::class)->createLogin(0, $user->getCompany()));
            $user->setAccountlevel(ACCOUNT_LEVEL_BUSINESS);
            $user->clearSubscription();
        } else {
            if (empty($user->getLogin()) && !empty($user->getEmail())) {
                $user->setLogin($this->loginGenerator->generate(
                    substr($user->getEmail(), 0, strpos($user->getEmail(), '@'))
                ));
            }
            $user->setRegistrationip($request->getClientIp());
            $user->setLastuseragent($request->server->get('HTTP_USER_AGENT'));

            if (!empty($userPassword = $user->getPass())) {
                $encoder = $this->encoderFactory->getEncoder($user);
                $user->setPass($encoder->encodePassword(trim($userPassword), null));
            }
            $n = 0;

            do {
                $refCode = StringHandler::getRandomCode(10);
                $n++;
            } while ($n < 5 && !empty($this->em->getRepository(Usr::class)->findOneBy(['refcode' => $refCode])));
            $user->setRefcode($refCode);
            $user->setInbeta(true);
            $user->setBetaapproved(true);

            /**
             * refs #23997, set default notification settings for new users.
             */
            $user->setEmailNewBlogPosts(NotificationModel::BLOGPOST_NEW_NOTIFICATION_WEEK);
            $user->setMpNewBlogPosts(true);
            $user->setWpNewBlogPosts(true);
        }

        $user->setLanguage($request->getLocale());

        // set US greeting for US users
        if (!$business && !StringHandler::isEmpty($ip = $user->getRegistrationip())) {
            $countryId = $this->geoLocation->getCountryIdByIp($ip);
            $user->setUsGreeting($countryId === Country::UNITED_STATES);
        }

        $this->em->persist($user);

        if ($business) {
            if ($user->getPicturever() instanceof UploadedFile) {
                $pic = clone $user->getPicturever();
                $user->setPicturever(null);
                $this->em->flush();
                $this->saveUploadedAvatarFile($user, $pic);
            }

            $businessInfo = new BusinessInfo($user, 0, 0, new \DateTime('+' . BusinessInfo::TRIAL_PERIOD_MONTH . ' month'));
            $this->em->persist($businessInfo);
        }
        $this->em->flush();
        $this->logger->info("created user account, userId: {$user->getUserid()}");

        $invite = false;
        $inviterId = $request->getSession()->get('inviterId');
        $invitesId = $request->getSession()->get('invId'); // todo check me
        $inviteCode = $request->getSession()->get('InviteCode', null);
        $invitesRep = $this->em->getRepository(Invites::class);
        $userRep = $this->em->getRepository(Usr::class);

        $this->securityLogger->info('invite state', ['UserID' => $user->getUserid(), 'InviterID' => $inviterId, 'InvitesID' => $invitesId, 'InviteCode' => $inviteCode]);

        // this email already invited ?
        if (empty($invitesId)) {
            $invite = $invitesRep->findOneBy([
                'inviterid' => empty($inviterId) ? $user->getUserid() : $inviterId,
                'email' => $user->getEmail(),
            ]);

            if (!empty($invite)) {
                $invitesId = $invite->getInvitesid();
            }
        }

        if ($inviteCode) {
            $invites = $invitesRep->findBy(['code' => $inviteCode, 'inviteeid' => null]);

            if (count($invites)) {
                $invite = $invites[0];
                $this->securityLogger->info('approving invite', ['UserID' => $user->getUserid(), 'InvitesID' => $invite->getInvitesid()]);
                $invite->setApproved(true);
                $invite->setEmail($user->getEmail());
                $invite->setInviteeid($user);
            }
        }
        // received invited user from facebook, twitter or direct link
        // generate record in Invites for stats
        elseif ($inviterId && !$invitesId) {
            $invite = new Invites();
            $invite->setInviteeid($user);
            $invite->setInviterid($userRep->find($inviterId));
            $invite->setEmail($user->getEmail());
            $invite->setInvitedate(new \DateTime());
            $invite->setApproved(true);
            $this->em->persist($invite);
            $this->securityLogger->info('generated invite for stats', ['UserID' => $user->getUserid(), 'InvitesID' => $invite->getInvitesid()]);
        } // received invited user from email
        elseif ($invitesId) {
            $invites = $invitesRep->findBy(['invitesid' => $invitesId, 'inviteeid' => null]);

            if (count($invites)) {
                $invite = $invites[0];
                $invite->setApproved(true);
                $invite->setEmail($user->getEmail());
                $invite->setInviteeid($user);
                $this->securityLogger->info('approving invite by invitesId', ['UserID' => $user->getUserid(), 'InvitesID' => $invite->getInvitesid()]);
            }
        }

        if ($invite) {
            $this->em->flush();

            if (!$invite->getInviterid()->isBusiness()) {
                $this->securityLogger->info('notifying inviter', ['UserID' => $user->getUserid(), 'InvitesID' => $invite->getInvitesid(), 'InviterID' => $invite->getInviterid()]);
                $this->UpdateInviterScore($invite->getInviterid());
                $this->notifyInviter($invite->getInviterid(), $user);
            }

            if ($familyMember = $invite->getFamilyMember()) {
                $this->securityLogger->info('copying family member data', ['UserID' => $user->getUserid(), 'FamilyMemberID' => $familyMember->getUseragentid()]);
                $user->setMidname(ucfirst(strtolower($familyMember->getMidname())));

                if ($familyMember->getPicturever()) {
                    $this->saveAvatarFile(
                        $user,
                        file_get_contents(
                            $this->container->get('kernel')->getProjectDir() . '/web' .
                            PicturePath(
                                '/images/uploaded/userAgent',
                                'original',
                                $familyMember->getUseragentid(),
                                $familyMember->getPicturever(),
                                $familyMember->getPictureext(),
                                'file'
                            )
                        )
                    );
                }
            }

            // link to business
            if ($invite->getInviterid()->isBusiness()) {
                $invBusiness = $invite->getInviterid();
            } else {
                $invBusiness = $userRep->getBusinessByUser($invite->getInviterid());
            }

            if ($invBusiness && !$user->getOwnedByBusiness()) {
                $user->setOwnedByBusiness($invBusiness);

                if ($userRep->getBusinessByUser($invite->getInviterid(), [ACCESS_BOOKING_VIEW_ONLY])) {
                    $user->setOwnedByManager($invite->getInviterid());
                }
            }

            $uaRep = $this->em->getRepository(Useragent::class);
            $inviteCodeRep = $this->em->getRepository(Invitecode::class);

            if ($inviteCode) {
                $inviteCodes = $inviteCodeRep->findBy(['code' => $inviteCode]);
            } else {
                $inviteCodes = $inviteCodeRep->findBy(['email' => $user->getEmail()]);
            }

            if (count($inviteCodes)) {
                $inviter = $userRep->find($inviteCodes[0]->getUserid());

                if (!empty($invite->getFamilyMember())) {
                    $this->accountManager->changeAccountsOwner(
                        OwnerRepository::getOwner($inviter, $invite->getFamilyMember()),
                        OwnerRepository::getOwner($user)
                    );
                    $this->timelineManager->changeItinerariesOwner(
                        OwnerRepository::getOwner($inviter, $invite->getFamilyMember()),
                        OwnerRepository::getOwner($user)
                    );
                }

                if ($inviter->isBusiness() && $invite->getFamilyMember()) {
                    $this->securityLogger->info('converting business family member to connection', ['UserID' => $user->getUserid(), 'FamilyMemberID' => $invite->getFamilyMember()->getUseragentid()]);

                    $userAgent = $invite->getFamilyMember();
                    $userAgent->setClientid($user);
                    $userAgent->setAccesslevel(UseragentRepository::ACCESS_WRITE);
                    $userAgent->setFirstname(null);
                    $userAgent->setMidname(null);
                    $userAgent->setLastname(null);
                    $userAgent->setEmail(null);
                    $userAgent->setBirthday(null);
                    $userAgent->setPopupShown(true);
                    $userAgent->setTripsharebydefault(true);
                    $userAgent->setSharebydefault(true);
                    $userAgent->setIsapproved(true);

                    $reverseUa = new Useragent();
                    $reverseUa->setAgentid($user);
                    $reverseUa->setClientid($userAgent->getAgentid());
                    $reverseUa->setAccesslevel(UseragentRepository::ACCESS_NONE);
                    $reverseUa->setSharebydefault(false);
                    $reverseUa->setTripsharebydefault(false);
                    $reverseUa->setIsapproved(true);
                    $reverseUa->setSendemails(true);
                    $reverseUa->setPopupShown(true);
                    $this->em->persist($reverseUa);
                    $this->em->persist($userAgent);
                    $this->em->persist($user);

                    $this->em->flush();

                    $accRep = $this->em->getRepository(Account::class);
                    $uaAccounts = $accRep->findBy(['user' => $invite->getInviterid(), 'userAgent' => $userAgent]);

                    foreach ($uaAccounts as $account) {
                        $this->securityLogger->info('changing owner of business family member account', ['UserID' => $user->getUserid(), 'AccountID' => $account->getAccountid()]);
                        $account->setUserid($user);
                        $account->setUseragentid(null);
                        $this->em->persist($account);
                    }

                    $this->em->flush();

                    foreach ($uaAccounts as $account) {
                        $accountShare = new Accountshare();
                        $accountShare->setUseragentid($userAgent);
                        $accountShare->setAccountid($account);
                        $this->em->persist($accountShare);
                    }

                    $this->em->flush();

                    $timelineRep = $this->em->getRepository(TimelineShare::class);
                    $timelineRep->addTimelineShare($userAgent);
                } elseif ($inviter) {
                    $this->securityLogger->info('approving invite', ['UserID' => $user->getUserid(), 'InviterID' => $inviter->getUserid(), 'InvitesID' => !empty($invite) ? $invite->getInvitesid() : null]);
                    $uaRep->inviteUser($inviter, $user, true, $invite);

                    if ($userAgent = $invite->getFamilyMember()) {
                        $this->em->getConnection()->executeQuery('
                            DELETE FROM TimelineShare WHERE FamilyMemberID = ?
                        ', [$userAgent->getId()], [\PDO::PARAM_INT]);
                    }
                }

                foreach ($inviteCodes as $code) {
                    $this->securityLogger->info('removing invite code', ['UserID' => $user->getUserid(), 'Code' => $code]);
                    $this->em->remove($code);
                }
            }

            $this->em->flush();
        }

        if ($giveTrial && !$user->isAwPlus()) {
            $this->securityLogger->info('giving trial', ['UserID' => $user->getUserid()]);
            /** @var Manager $cartManager */
            $cartManager = $this->container->get('aw.manager.cart');

            $trialClass = in_array((int) $ref, Sitead::BLOG_AWPLUS_6MONTHS_ID, true)
                ? AwPlusTrial6Months::class
                : AwPlusTrial::class;

            $cartManager
                ->setUser($user)
                ->giveAwPlusTrial($trialClass);
        }

        if (!StringHandler::isEmpty($user->getRegistrationip())) {
            $this->checkFakeUsers($user->getRegistrationip());
        }

        // Create avatar
        if ($user->getPicturever() && $user->getPicturever() instanceof UploadedFile) {
            $this->saveUploadedAvatarFile($user, $user->getPicturever());
        } else {
            $url = urldecode('http://www.gravatar.com/avatar/' . md5(strtolower($user->getEmail())) . '?s=3000&d=404');
            $avatar = curlRequest($url);
            $this->saveAvatarFile($user, $avatar);
        }

        if (170 == $ref && !empty($tid)) {
            $http = new \HttpBrowser('none', $this->container->get('aw.curl_driver'));
            $http->GetURL('https://tracker.emiles.com/convert?tid=' . $tid . '&ctid=' . $user->getRefcode());
        }

        if (!$business) {
            $this->em->getConnection()->executeQuery(
                'DELETE FROM DoNotSend WHERE Email = ?',
                [$user->getEmail()],
                [\PDO::PARAM_STR]
            );
        }

        if ($sendEmail) {
            $this->sendGreeting($user);
        }

        return $user;
    }

    /**
     * @param resource $data
     */
    public function saveAvatarFile(Usr $user, $data)
    {
        if (!isset($data) || !$data) {
            return;
        }

        try {
            $avatarCreator = new AvatarCreator($data);
        } catch (\Exception $e) {
            return;
        }

        $avatarCreator->createAvatarsForUser($user);
        $user->setPictureext($avatarCreator->getExtension());
        $user->setPicturever($avatarCreator->getVersion());
        $this->em->flush($user);
    }

    public function saveUploadedAvatarFile(Usr $user, UploadedFile $uploadedFile)
    {
        $this->saveAvatarFile($user, file_get_contents($uploadedFile->getPathname()));
    }

    public function setInviteByCouponUser(Usr $user, Usr $inviter)
    {
        $invitesRep = $this->em->getRepository(Invites::class);
        $usrRep = $this->em->getRepository(Usr::class);

        $invitesByInvitee = $invitesRep->findBy(['inviteeid' => $user]);
        $invitesByInviter = $invitesRep->findBy(['inviterid' => $inviter, 'email' => $user->getEmail()]);

        if (!count($invitesByInvitee) && !count($invitesByInviter)) {
            $invite = new Invites();
            $invite->setInviteeid($user);
            $invite->setInviterid($inviter);
            $invite->setEmail($user->getEmail());
            $invite->setInvitedate(new \DateTime());
            $invite->setApproved(true);
            $this->em->persist($invite);

            $this->em->flush();

            $newStars = $this->UpdateInviterScore($inviter);
            $this->notifyInviter($inviter, $user, $newStars);

            // link to business
            if (($businessUser = $usrRep->getBusinessByUser($invite->getInviterid())) && !$user->getOwnedByBusiness()) {
                $user->setOwnedByBusiness($businessUser);

                if ($usrRep->getBusinessByUser($invite->getInviterid(), [ACCESS_BOOKING_VIEW_ONLY])) {
                    $user->setOwnedByManager($invite->getInviterid());
                }
            }

            $this->em->flush();
        }
    }

    public function notifyInviter(Usr $inviter, Usr $invitee, $new = 0)
    {
        $acceptedCount = $this->getLiveReferralsCount($inviter);
        $stars = $this->em->getRepository(Coupon::class)->getInviteCouponByUser($inviter);

        if ($stars * 5 > $acceptedCount && 0 === $new) {
            // do not send emails if inviter has negative or zero invited vs. alive referrals score
            return;
        }
        $toGo = 5 - ($acceptedCount % 5);

        if (!$inviter->getEmailInviteeReg()) {
            // do not send emails if inviter has unchecked "Email me when people that I invite register on AwardWallet"
            return;
        }

        $mailer = $this->container->get('aw.email.mailer');

        if ($stars > 0 && 5 === $toGo) {
            $qb = $this->em->createQueryBuilder();
            $qb->select('c')
                ->from(Coupon::class, 'c')
                ->where('c.code like :code')->setParameter('code', 'Invite-' . $inviter->getUserid() . '-%')
                ->orderBy('c.creationdate', 'desc');
            $coupons = $qb->getQuery()->getResult();

            if (!count($coupons)) {
                throw new \Exception('missing coupon, refs #6132');
            }
            /** @var Coupon $coupon */
            $coupon = $coupons[0];

            if ($coupon->getNumberOfUses()) {
                throw new \Exception('missing coupon, refs #6132');
            }

            $template = new FreeUpgrade($inviter);
            $template->invitee = $invitee;
            $template->coupon = $coupon;

            $message = $mailer->getMessageByTemplate($template);
        } else {
            $template = new UserRegisteredPerRequest($inviter);
            $template->invitee = $invitee;
            $template->usersNeeded = $toGo;

            $message = $mailer->getMessageByTemplate($template);
        }
        $mailer->send($message);
    }

    public function sendVerificationMail(Usr $user)
    {
        $mailer = $this->container->get('aw.email.mailer');
        $message = $mailer->getMessageByTemplate(new EmailVerification($user));
        $mailer->send($message, [
            Mailer::OPTION_SKIP_DONOTSEND => true,
        ]);
    }

    /**
     * @return int
     * @throws ORMInvalidArgumentException
     */
    public function updateInviterScore(Usr $user)
    {
        $acceptedCount = $this->getLiveReferralsCount($user);
        $totalStars = floor($acceptedCount / 5);
        $actualStars = $this->em->getRepository(Coupon::class)->getInviteCouponByUser($user);

        while ($totalStars > $actualStars) {
            $code = 'Invite-' . $user->getUserid() . '-' . RandomStr(ord('A'), ord('Z'), 5);
            $coupon = new Coupon();
            $coupon->setCode($code);
            $coupon->setName('Invite bonus');
            $coupon->setMaxuses(1);
            $coupon->setDiscount(100);
            $coupon->setFirsttimeonly(false);
            $this->em->persist($coupon);
            $this->logger->warning('issued invite coupon', ['TotalStars' => $totalStars, 'ActualStars' => $actualStars, 'Correction' => $user->getInviteCouponsCorrection(), 'AcceptedCount' => $acceptedCount, 'Code' => $code, 'UserID' => $user->getUserid()]);
            $totalStars--;
        }
        $this->em->flush();

        $new = $totalStars - $actualStars;

        return $new > 0 ? $new : 0;
    }

    /**
     * check if user already was in new interface.
     */
    public function checkUserWasInBeta()
    {
        if (!$this->session->has('wasInBeta')) {
            $this->session->set('wasInBeta', true);
            $this->session->getFlashBag()->set('betaWelcome', true);
        }
    }

    public function sendRestorePasswordEmail(Request $request, $username)
    {
        $error = $this->forgotLocker->checkForLockout($request->getClientIp());

        if (!empty($error)) {
            return $error;
        }

        if (!(isset($username) && is_string($username))) {
            return $this->translator->trans('landing.dialog.forgot.help');
        }

        $error = $this->forgotLocker->checkForLockout($username);

        if (!empty($error)) {
            return $error;
        }

        /** @var $user Usr */
        $user = $this->em->getRepository(Usr::class)->loadUserByUsername($username, false);

        if (!$user) {
            return $this->translator->trans('landing.dialog.forgot.help');
        }

        $resetCode = md5($user->getPass() . time() . rand(0, 10000));
        $user->setResetpasswordcode($resetCode);
        $user->setResetpassworddate(new \DateTime('now'));
        $this->em->persist($user);
        $this->em->flush();

        $mailer = $this->container->get('aw.email.mailer');
        $template = new ResetPassword($user, $this->container->get('security.authorization_checker')->isGranted('SITE_BUSINESS_AREA'));
        $message = $mailer->getMessageByTemplate($template);
        $mailer->send(
            $message,
            [
                Mailer::OPTION_SKIP_DONOTSEND => true,
                Mailer::OPTION_EXTERNAL_OPEN_TRACKING => false,
                Mailer::OPTION_EXTERNAL_CLICK_TRACKING => false,
            ]
        );

        return true;
    }

    public function changePassword(Usr $user, $newPassword)
    {
        if (null === $newPassword) {
            throw new \UnexpectedValueException('Password should not be NULL');
        }

        $currentDevice = $this->mobileDeviceManager->getCurrentDevice();

        if ($currentDevice) {
            $this->eventDispatcher->addListener(
                KernelEvents::RESPONSE,
                fn (ResponseEvent $event) => $event->getRequest()->attributes->set(
                    MobileDeviceManager::REQUEST_ATTRIBUTE_DEVICE_ID,
                    $currentDevice->getMobileDeviceId()
                ),
                1000 // set ASAP
            );
        }

        $conn = $this->em->getConnection();
        $conn->prepare('DELETE FROM MobileKey WHERE UserID = ? and Kind = ?')->execute([$user->getUserid(), self::KEY_KIND_JSON]);
        $encoder = $this->encoderFactory->getEncoder($user);
        $user->setPass($encoder->encodePassword($newPassword, null));
        $user->setResetpasswordcode(null);
        $user->setResetpassworddate(null);
        $user->setChangePasswordDate(new \DateTime());
        $this->securityLogger->info('changing password', ['UserID' => $user->getUserid()]);

        if (!($this->tokenStorage->getToken()->getUser() instanceof Usr && $this->tokenStorage->getToken()->getUser()->getUserid() == $user->getUserid())) {
            $this->securityLogger->info('password was reset by reset password link', ['UserID' => $user->getUserid()]);
            $user->setChangePasswordMethod(Usr::CHANGE_PASSWORD_METHOD_LINK);
        } else {
            $user->setChangePasswordMethod(Usr::CHANGE_PASSWORD_METHOD_PROFILE);
        }

        $this->em->flush($user);
        $sessionId =
            $this->session
            && !StringHandler::isEmpty($sessionId = $this->session->getId()) ?
                $sessionId :
                null;

        if (isset($sessionId)) {
            $this->tokenProvider->deleteTokenByUserIdExceptCurrentSession($user->getId(), $sessionId);
        } else {
            $this->tokenProvider->deleteTokenByUserId($user->getId());
        }

        $this->refreshToken();

        if (isset($sessionId)) {
            $this->session->invalidate();
            $this->sessionListener->invalidateUserSessionsButCurrent($user->getId(), $sessionId);
        }

        // send mail
        $mailer = $this->container->get('aw.email.mailer');
        $template = new PasswordChanged($user);
        $message = $mailer->getMessageByTemplate($template);
        $mailer->send($message, [
            Mailer::OPTION_SKIP_DONOTSEND => true,
        ]);
    }

    public function checkUniqueUser(Usr $user)
    {
        $usrRep = $this->em->getRepository(Usr::class);

        $criteria = Criteria::create()
            ->where(Criteria::expr()->eq('email', $user->getEmail()))
            ->orWhere(Criteria::expr()->eq('login', $user->getLogin()));

        return $usrRep->matching($criteria)->count() == 0;
    }

    public function loginUserByKey(Usr $user, $business, $url, $loginType)
    {
        $targetDomain = $this->container->getParameter('host');

        if ($business) {
            $targetDomain = $this->container->getParameter('business_host');

            if ($url == '/') {
                if (!empty($user->getBookerInfo())) {
                    $url = $this->container->get('router')->generate('aw_booking_list_queue');
                } else {
                    $url = $this->container->get('router')->generate(
                        'aw_business_home',
                        [],
                        UrlGeneratorInterface::ABSOLUTE_URL
                    );
                }
            }
        }
        $token = $this->container->get('aw.security.token_storage')->getToken();
        $originalToken = $token instanceof SwitchUserToken ? $token->getOriginalToken() : $token;

        $this->logger->warning('impersonating', ['OriginalTokenClass' => get_class($originalToken), 'TokenClass' => get_class($token)]);
        $request = [
            'LoginType' => $loginType,
            'TargetURL' => $url,
            'AwPlus' => Utils::tokenHasRole($token, 'ROLE_AWPLUS'),
            'OriginalToken' => serialize($originalToken),
        ];
        $key = $this->createMobileKey($user->getUserid(), self::KEY_KIND_IMPERSONATE, $request);

        return new Response($this->container->get('twig')->render('@AwardWalletMain/postRedirectPage.html.twig', [
            'url' => $this->container->getParameter('requires_channel') . '://' . $targetDomain . $this->router->generate('aw_security_login_by_key'),
            'form' => [
                'UserID' => $user->getUserid(),
                'Key' => $key,
            ],
        ]));
    }

    public function impersonate(Usr $user, $full, $awPlus, $goto)
    {
        $ur = $this->em->getRepository(Usr::class);
        $authChecker = $this->container->get('security.authorization_checker');

        if ($full) {
            if (!$authChecker->isGranted('FULL_IMPERSONATE')) {
                throw new AuthenticationException('You are not allowed to use full impersonate');
            }
            $loginType = UserManager::LOGIN_TYPE_IMPERSONATE_FULLY;
        } else {
            if (!$authChecker->isGranted('ROLE_MANAGE_IMPERSONATE')) {
                throw new AuthenticationException('You are not allowed to use impersonate');
            }
            $loginType = UserManager::LOGIN_TYPE_IMPERSONATE;
        }

        if ($user->getAccountLevel() == ACCOUNT_LEVEL_BUSINESS) {
            /** @var Usr $user */
            $admins = $ur->getBusinessAdmins($user);

            return $this->loginUserByKey(array_shift($admins), true, $goto, $loginType);
        } else {
            $this->loadToken($user, false, $loginType, $awPlus);

            return new RedirectResponse($goto);
        }
    }

    public function findUser($userIdOrLoginOrEmail, $allowBusiness): Usr
    {
        $ur = $this->em->getRepository(Usr::class);
        $user = $ur->find($userIdOrLoginOrEmail);

        if (empty($user)) {
            $user = $ur->findOneBy(['login' => $userIdOrLoginOrEmail]);
        }

        if (empty($user)) {
            $user = $ur->findOneBy(['email' => $userIdOrLoginOrEmail]);
        }

        if (empty($user)) {
            throw new AuthenticationException("We could not find user '{$userIdOrLoginOrEmail}'");
        }

        /** @var Usr $user */
        if (!$allowBusiness && $user->getAccountlevel() == ACCOUNT_LEVEL_BUSINESS) {
            throw new AuthenticationException('You are not allowed to login to business user');
        }

        return $user;
    }

    /**
     * @return [$user, $errorMessage]
     */
    public function checkUserLogin($loginOrEmail, $password)
    {
        if (empty($loginOrEmail)) {
            return [null, 'Username or password in invalid'];
        }
        $request = $this->container->get('request_stack')->getMasterRequest();

        try {
            $user = $this->findUser($loginOrEmail, false);
        } catch (AuthenticationException $e) {
            $user = null;
        }

        if (!empty($user)) {
            $passwordChecker = $this->container->get('aw.security.password_checker');

            if (!$passwordChecker->checkPasswordSafe($user, $password, $request->getClientIp(), $lockerError)) {
                return [null, $lockerError];
            }

            return [$user, null];
        } else {
            $error = $this->container->get('aw.security.antibruteforce.ip')->checkForLockout($request->getClientIp());

            if (!empty($error)) {
                return [null, $error];
            } else {
                return [null, 'Username or password in invalid'];
            }
        }
    }

    public function isEmailHostValid(string $email): bool
    {
        $emailParts = \mb_split('@', $email);

        if (count($emailParts) !== 2) {
            return false;
        }

        $hasMx = $this->mxRecordChecker->check($emailParts[1]);

        if (!$hasMx) {
            $this->logger->info('Email host is not valid', ['email' => $email, 'host' => $emailParts[1]]);
        }

        return $hasMx;
    }

    /**
     * @param string $userRegistrationIp
     */
    protected function checkFakeUsers($userRegistrationIp)
    {
        foreach ($this->em->getConnection()->executeQuery(
            '
            SELECT
                InviterID,
                InviterLogin,
                GROUP_CONCAT(InviteeID SEPARATOR \',\') as Invitees,
                COUNT(InviteeID)
            FROM (SELECT
                    COUNT(a.AccountID) AS AccountsCount,
                    i.InviterID,
                    u.UserID AS InviteeID,
                    up.Login as InviterLogin
                FROM Usr u
                LEFT JOIN Account a ON u.UserID = a.UserID
                LEFT JOIN Invites i ON u.UserID = i.InviteeID
                JOIN Usr up ON i.InviterID = up.UserID
                WHERE 
                    u.RegistrationIP = ?
                    AND u.CameFrom = 4
                    AND u.CreationDateTime >= ADDDATE(NOW(), INTERVAL -1 HOUR)
                    AND i.InviterID IS NOT NULL
                GROUP BY i.InviterID, u.UserID, up.Login 
                HAVING COUNT(a.AccountID) = 0
            ) stat
            GROUP BY InviterID, InviterLogin
            HAVING COUNT(InviteeID) > 2',
            [$userRegistrationIp],
            [\PDO::PARAM_STR]
        ) as $fakeInviter) {
            // sort invitees for test purposes
            $invitees = array_map('intval', explode(',', $fakeInviter['Invitees']));
            sort($invitees);
            $invitees = implode(', ', $invitees);

            $this->appBot->send(Slack::CHANNEL_AW_FAKE_IDS,
                "*Potentially fake IDs*: {$invitees}
    *Registered from IP*: {$userRegistrationIp}
    *Inviter*: _ID_ {$fakeInviter['InviterID']}  _Login_ {$fakeInviter['InviterLogin']}  
    <https://awardwallet.com/manager/reports/fakeAccounts.php?Inviter={$fakeInviter['InviterID']}#{$fakeInviter['InviterID']}|Delete All>");
        }
    }

    // todo move to invites rep
    protected function getLiveReferralsCount(Usr $user)
    {
        $sql = "
            SELECT COUNT(InvitesID) AS accepted
            FROM Invites
            WHERE
                InviterID = ? AND
                /* don't count unapproved invites and deleted referrals */
                Approved = 1 AND
                InviteeID IS NOT NULL";
        $stmt = $this->em->getConnection()->executeQuery($sql, [$user->getUserid()], [\PDO::PARAM_INT]);
        $row = $stmt->fetch(\PDO::FETCH_ASSOC);

        return (int) $row['accepted'];
    }

    private function createMobileKey($userId, $kind, $params = null, $accessLevel = null)
    {
        $result = StringHandler::getRandomCode(20);

        if (!isset($accessLevel)) {
            $accessLevel = self::KEY_ACCESS_BALANCE;
        }

        $connection = $this->container->get('database_connection');
        $connection->executeUpdate(
            'insert into MobileKey(UserID, MobileKey, CreateDate, AskPasswordDate, Kind, Params, AccessLevel)
   		    values(:userId, :key, now(), now(), :kind, :params, :accessLevel)',
            ['userId' => $userId, 'key' => $result, 'kind' => $kind, 'params' => serialize($params), 'accessLevel' => $accessLevel]
        );

        return $result;
    }

    private function sendGreeting(Usr $user)
    {
        $mailer = $this->container->get('aw.email.mailer');

        if ($user->isUsGreeting()) {
            $producer = $this->container->get(Producer::class);
            $now = date_create('now', new \DateTimeZone('America/New_York'));

            // first email
            $firstEmailDeadline = (clone $now)->modify('+23 hour');

            // second email
            $secondEmailSendDateTime = (clone $now)->modify('+1 day')->setTime(9, 0, 0);

            // if send time is less than 24 hours from now, send it next day
            if ($secondEmailSendDateTime->getTimestamp() - $now->getTimestamp() < 24 * 60 * 60) {
                $secondEmailSendDateTime->modify('+1 day');
            }

            $secondEmailDeadline = (clone $secondEmailSendDateTime)->modify('+6 hour');

            // third email
            $thirdEmailSendDateTime = (clone $secondEmailSendDateTime)->modify('+1 day');
            $thirdEmailDeadline = (clone $thirdEmailSendDateTime)->modify('+6 hour');

            $this->logger->info(
                sprintf(
                    'sending welcome emails to user %d, #1: %s, #2: %s, #3: %s',
                    $user->getId(),
                    json_encode([
                        'send' => $now->format('Y-m-d H:i:s'),
                        'deadline' => $firstEmailDeadline->format('Y-m-d H:i:s'),
                    ]),
                    json_encode([
                        'send' => $secondEmailSendDateTime->format('Y-m-d H:i:s'),
                        'deadline' => $secondEmailDeadline->format('Y-m-d H:i:s'),
                    ]),
                    json_encode([
                        'send' => $thirdEmailSendDateTime->format('Y-m-d H:i:s'),
                        'deadline' => $thirdEmailDeadline->format('Y-m-d H:i:s'),
                    ])
                )
            );

            $producer->publish(
                new EmailTask(
                    $user->getId(),
                    WelcomeToAwUsAccountList::class,
                    true,
                    $firstEmailDeadline->getTimestamp()
                ),
                null,
                Producer::PRIORITY_NORMAL
            );
            $producer->publish(
                new EmailTask(
                    $user->getId(),
                    WelcomeToAwUsTimeline::class,
                    false,
                    $secondEmailDeadline->getTimestamp()
                ),
                $secondEmailSendDateTime->getTimestamp() - $now->getTimestamp(),
            );
            $producer->publish(
                new EmailTask(
                    $user->getId(),
                    WelcomeToAwUsMailbox::class,
                    false,
                    $thirdEmailDeadline->getTimestamp()
                ),
                $thirdEmailSendDateTime->getTimestamp() - $now->getTimestamp(),
            );

            return;
        }

        $template = new WelcomeToAw($user);
        $message = $mailer->getMessageByTemplate($template);
        $mailer->send($message, [
            Mailer::OPTION_SKIP_DONOTSEND => true,
        ]);
    }
}
