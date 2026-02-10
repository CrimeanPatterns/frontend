<?php

namespace AwardWallet\MainBundle\Security;

use AwardWallet\MainBundle\Entity\Geo\Adapters\UsrDivisionsAdapter;
use AwardWallet\MainBundle\Entity\Repositories\UsrRepository;
use AwardWallet\MainBundle\Entity\Usr;
use AwardWallet\MainBundle\FrameworkExtension\AwTokenStorageInterface;
use AwardWallet\MainBundle\FrameworkExtension\Error\SimpleLeveledErrorReporter;
use AwardWallet\MainBundle\Globals\UserAgentUtils;
use AwardWallet\MainBundle\Security\RememberMe\RememberMeServices;
use AwardWallet\MainBundle\Service\EntitySerializer;
use AwardWallet\MainBundle\Service\GeoLocation\GeoLocation;
use AwardWallet\MainBundle\Service\GeoLocation\UpdateUserIPPointQuery;
use AwardWallet\MainBundle\Service\GeoLocation\UpdateUsrLastLogonPointOutboxQuery;
use Doctrine\DBAL\Exception\DeadlockException;
use Doctrine\ORM\EntityManagerInterface;
use Monolog\Logger;
use Psr\Log\LoggerInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestMatcher;
use Symfony\Component\HttpKernel\Event\GetResponseForExceptionEvent;
use Symfony\Component\Security\Core\Authentication\RememberMe\TokenProviderInterface;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;
use Symfony\Component\Security\Core\Exception\AuthenticationException;
use Symfony\Component\Security\Csrf\CsrfTokenManagerInterface;
use Symfony\Component\Security\Http\Event\InteractiveLoginEvent;
use Symfony\Component\Security\Http\Event\SwitchUserEvent;

class AuthenticationListener
{
    public const SWITCH_USER_FIRED = 'switch_user_listener_fired';

    protected TokenProviderInterface $tokenProvider;
    protected AntiBruteforceLockerService $loginLocker;
    protected AntiBruteforceLockerService $ipLocker;
    protected CsrfTokenManagerInterface $tokenManager;
    protected UsrRepository $userRepo;
    private LoggerInterface $logger;
    private AwTokenStorageInterface $tokenStorage;
    private EntitySerializer $entitySerializer;
    private AuthorizationCheckerInterface $authorizationChecker;
    private GeoLocation $geoLocation;
    private UpdateUsrLastLogonPointOutboxQuery $updateUsrLatLngQuery;
    private EntityManagerInterface $entityManager;
    private UpdateUserIPPointQuery $updateUserIPPointQuery;
    private SimpleLeveledErrorReporter $errorReporter;

    public function __construct(
        TokenProviderInterface $tokenProvider,
        AntiBruteforceLockerService $loginLocker,
        AntiBruteforceLockerService $ipLocker,
        CsrfTokenManagerInterface $tokenManager,
        UsrRepository $userRepo,
        LoggerInterface $logger,
        AwTokenStorageInterface $tokenStorage,
        EntitySerializer $entitySerializer,
        AuthorizationCheckerInterface $authorizationChecker,
        GeoLocation $geoLocation,
        UpdateUsrLastLogonPointOutboxQuery $updateUsrLatLngQuery,
        UpdateUserIPPointQuery $updateUserIPPointQuery,
        EntityManagerInterface $entityManager,
        SimpleLeveledErrorReporter $errorReporter
    ) {
        $this->tokenProvider = $tokenProvider;
        $this->loginLocker = $loginLocker;
        $this->ipLocker = $ipLocker;
        $this->tokenManager = $tokenManager;
        $this->userRepo = $userRepo;
        $this->logger = $logger;
        $this->tokenStorage = $tokenStorage;
        $this->entitySerializer = $entitySerializer;
        $this->authorizationChecker = $authorizationChecker;
        $this->geoLocation = $geoLocation;
        $this->entityManager = $entityManager;
        $this->updateUsrLatLngQuery = $updateUsrLatLngQuery;
        $this->updateUserIPPointQuery = $updateUserIPPointQuery;
        $this->errorReporter = $errorReporter;
    }

    /**
     * Handles security related exceptions.
     *
     * @param GetResponseForExceptionEvent $event An GetResponseForExceptionEvent instance
     */
    public function onCoreException(GetResponseForExceptionEvent $event)
    {
        $exception = $event->getThrowable();

        if (!($exception instanceof AuthenticationException || $exception instanceof AccessDeniedException)) {
            return;
        }

        $request = $event->getRequest();
        $requestMatcher = new RequestMatcher();
        // new mobile
        $requestMatcher->matchPath('^/m/api');

        if (
            (false !== strpos($request->headers->get('Accept'), 'application/json'))
            && $requestMatcher->matches($request)
        ) {
            $event->setResponse(new JsonResponse(
                array_merge(
                    ['error' => 'Access denied'],
                    !$this->tokenStorage->getUser() ?
                        ['logout' => true] :
                        []
                ),
                403
            ));

            return;
        }
    }

    /**
     * Triggered by both form and remember-me authentication.
     */
    public function onInteractiveLogin(InteractiveLoginEvent $event)
    {
        $user = $event->getAuthenticationToken()->getUser();

        if ($user instanceof Usr) {
            $request = $event->getRequest();

            $user->setLogoncount($user->getLogoncount() + 1);
            $user->setLastlogondatetime(new \DateTime());
            $lastLogonIp = $request->getClientIp();
            $user->setLastlogonip($lastLogonIp);
            $user->setLastuseragent($request->server->get('HTTP_USER_AGENT'));

            if (null !== $lastLogonIp) {
                $geoResult = $this->geoLocation->getAwGeoResultByIp($lastLogonIp);
                $this->geoLocation->updateGeoDataByAwGeoResult(
                    new UsrDivisionsAdapter($user),
                    $geoResult
                );
            }

            $this->logger->info("interactive login", ["UserID" => $user->getUserid(), "IP" => $user->getLastlogonip(), "UserAgent" => $user->getLastuseragent(), "CountryID" => $user->getCountryid(), "IsStaff" => $user->hasRole("ROLE_STAFF")]);
            $this->entityManager->persist($user);
            $connection = $this->entityManager->getConnection();
            $connection->beginTransaction();

            try {
                // keep this flush and UpdateUserIPPointQuery ordered like this, because of deadlocks
                $this->entityManager->flush();
                $geoPoint = $geoResult->getPoint();
                $lat = $geoPoint[0] ?? null;
                $lng = $geoPoint[1] ?? null;
                $this->updateUsrLatLngQuery->execute($user->getId(), $lat, $lng);
                $this->updateUserIPPointQuery->execute($user->getId(), $lastLogonIp, $lat, $lng);
                $connection->commit();
            } catch (\Throwable $e) {
                $connection->rollBack();

                // ignore deadlocks
                if ($e instanceof DeadlockException) {
                    $this->errorReporter->logThrowable($e, Logger::ERROR);
                } else {
                    throw $e;
                }
            }

            // optimization, save business user to security context, see SessionListener
            $businessId = $this->userRepo->getBusinessIdByUserAdmin($user->getId());
            $session = $request->getSession();

            // because of this key in SessionListener the following happens:
            // $sessionUser->_setBusinessByLevel($businessUser, [ACCESS_ADMIN, ACCESS_BOOKING_MANAGER, ACCESS_BOOKING_VIEW_ONLY])
            // and as a result \AwardWallet\MainBundle\Entity\Repositories\UsrRepository::getBusinessByUser
            // returns business user for a user who does not have access to the business
            // enough already to support this piece of shit \AwardWallet\MainBundle\Entity\Usr::$_BusinessByLevel

            $session->set("BusinessUserID_" . $user->getId(), $businessId);

            if ($this->authorizationChecker->isGranted('SITE_BUSINESS_AREA')) {
                $this->populateOldSession($this->userRepo->find($businessId));
                $_SESSION['ManagerFields'] = $this->entitySerializer->entityToArray($user);
            } else {
                $this->populateOldSession($user);
            }
            $this->tokenManager->refreshToken("");
            ResetFormToken();

            $businessAccess = [ACCESS_ADMIN, ACCESS_BOOKING_MANAGER, ACCESS_BOOKING_VIEW_ONLY];
            $user->_setBusinessByLevel(!empty($businessId) ? $this->userRepo->find($businessId) : null, $businessAccess);
            $this->populateSession($request);

            $this->setUserAuthStat([
                'UserID' => $user->getId(),
                'CountryID' => isset($geoResult) ? $geoResult->getCountryId() : null,
                'IP' => $request->getClientIp(),
                'UserAgent' => $request->headers->get('User-Agent', ''),
                'Lang' => $request->headers->get('Accept-Language', ''),
                'uri' => $request->getRequestUri(),
            ]);
        }
    }

    public static function cleanOldSession()
    {
        if (isset($_SESSION)) {
            foreach (['UserID', 'UserFields', 'ManagerFields', 'Login', 'FirstName', 'LastName', 'Email', 'Zip', 'City', 'CountryID', 'StateID', 'EmailVerified', 'UserName', 'AccountLevel', 'UserAgentID'] as $key) {
                unset($_SESSION[$key]);
            }
        }
    }

    public function onSwitchUser(SwitchUserEvent $event)
    {
        $request = $event->getRequest();
        /** @var Usr $user */
        $user = $event->getTargetUser();

        if (!empty($user->getId())) {
            if ($this->authorizationChecker->isGranted('SITE_BUSINESS_AREA')) {
                $businessId = $this->userRepo->getBusinessIdByUserAdmin($user->getId());

                if (empty($businessId)) {
                    self::cleanOldSession();
                } else {
                    $this->populateOldSession($this->userRepo->find($businessId));
                }
                $_SESSION['ManagerFields'] = $this->entitySerializer->entityToArray($user);
            } else {
                $this->populateOldSession($user);
            }
            $this->populateSession($request);
        } else {
            $this->logger->warning("impersonate finished");
            self::cleanOldSession();
        }

        $request->attributes->set(self::SWITCH_USER_FIRED, true);
    }

    private function logAuthExceptionWarning(\Exception $exception, array $logContext)
    {
        $this->logger->warning("auth exception", array_merge($logContext, $this->getExceptionContext($exception)));
    }

    private function logAuthExceptionCritical(\Exception $exception, array $logContext)
    {
        $this->logger->critical("auth exception", array_merge($logContext, $this->getExceptionContext($exception)));
    }

    private function getExceptionContext(\Exception $exception)
    {
        return [
            "exception" => $this->getSafeExceptionInfo($exception),
            "previousException" => ($previousException = $exception->getPrevious()) ? $this->getSafeExceptionInfo($previousException) : null,
        ];
    }

    private function getSafeExceptionInfo(\Exception $exception)
    {
        return array_merge(
            [
                'class' => get_class($exception),
                'file' => $exception->getFile(),
                'line' => $exception->getLine(),
                'message' => $exception->getMessage(),
                'code' => $exception->getCode(),
            ],
            $exception instanceof AuthenticationException ?
                [
                    'messageKey' => $exception->getMessageKey(),
                    'messageData' => $exception->getMessageData(),
                ] :
                []
        );
    }

    private function populateOldSession(Usr $user)
    {
        self::cleanOldSession();
        $_SESSION["UserFields"] = $this->entitySerializer->entityToArray($user);
        $_SESSION["UserID"] = $user->getUserid();
        $_SESSION["Login"] = $user->getLogin();
        $_SESSION["FirstName"] = $user->getFirstname();
        $_SESSION["LastName"] = $user->getLastname();
        $_SESSION["Email"] = $user->getEmail();
        $_SESSION["Zip"] = $user->getZip();
        $_SESSION["City"] = $user->getCity();
        $_SESSION["CountryID"] = $user->getCountryid();
        $_SESSION["StateID"] = $user->getStateid();
        $_SESSION["EmailVerified"] = $user->getEmailverified();
        $_SESSION["UserName"] = UserName($_SESSION["UserFields"]);
        $_SESSION["AccountLevel"] = $user->getAccountlevel();
        $_SESSION["UserAgentID"] = "All";
    }

    private function populateSession(Request $request)
    {
        // we will track IP and UA changes in SessionListener
        $session = $request->getSession();

        if (!empty($session)) { // cli
            $session->set('IP', $request->getClientIp());
            $session->set('LogonIP', $request->getClientIp());
            $session->set('UserAgent', $request->headers->get('User-Agent'));
            $session->set('LogonUserAgent', $request->headers->get('User-Agent'));
            $session->set('RememberMeTokenID', $request->attributes->get(RememberMeServices::REQUEST_ATTR_TOKEN_ID));
        }
    }

    private function setUserAuthStat(array $stat): void
    {
        $browser = UserAgentUtils::getBrowser($stat['UserAgent']);
        $type = 0 === stripos($stat['uri'], '/m/') ? 1 : 2;

        $this->entityManager->getConnection()->executeQuery('
            INSERT INTO UserAuthStat (UserID, Browser, Platform, CountryID, IP, UserAgent, Lang, IsMobile, IsDesktop, AuthType, CreateDate)
                VALUES (:userId, :browser, :platform, :countryId, :ip, :userAgent, :lang, :isMobile, :isDesktop, :type, :createDate)
                ON DUPLICATE KEY UPDATE
                       Counter = Counter + 1',
            [
                ':userId' => $stat['UserID'],
                ':browser' => $browser['browser'],
                ':platform' => $browser['platform'],
                ':countryId' => $stat['CountryID'],
                ':ip' => $stat['IP'],
                ':userAgent' => $stat['UserAgent'],
                ':lang' => substr($stat['Lang'], 0, 64),
                ':isMobile' => $browser['isMobile'],
                ':isDesktop' => $browser['isDesktop'],
                ':type' => $type,
                ':createDate' => date('Y-m-d H:i:s'),
            ],
            [
                ':userId' => \PDO::PARAM_INT,
                ':browser' => \PDO::PARAM_STR,
                ':platform' => \PDO::PARAM_STR,
                ':countryId' => \PDO::PARAM_INT,
                ':ip' => \PDO::PARAM_STR,
                ':userAgent' => \PDO::PARAM_STR,
                ':lang' => \PDO::PARAM_STR,
                ':isMobile' => \PDO::PARAM_INT,
                ':isDesktop' => \PDO::PARAM_INT,
                ':type' => \PDO::PARAM_INT,
                ':createDate' => \PDO::PARAM_STR,
            ]
        );
    }
}
