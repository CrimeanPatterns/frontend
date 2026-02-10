<?php

namespace AwardWallet\MainBundle\Security;

use AwardWallet\Common\Monolog\Processor\AppProcessor;
use AwardWallet\MainBundle\Entity\Usr;
use AwardWallet\MainBundle\FrameworkExtension\AwTokenStorageInterface;
use AwardWallet\MainBundle\FrameworkExtension\Exceptions\ImpersonatedException;
use AwardWallet\MainBundle\Globals\Geo;
use AwardWallet\MainBundle\Globals\Headers\MobileHeaders;
use AwardWallet\MainBundle\Globals\UserAgentUtils;
use AwardWallet\MainBundle\Security\RememberMe\RememberMeServices;
use Doctrine\DBAL\Connection;
use Doctrine\ORM\EntityManager;
use Psr\Log\LoggerInterface;
use Symfony\Bundle\FrameworkBundle\Routing\Router;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestMatcher;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpFoundation\Session\Session;
use Symfony\Component\HttpFoundation\Session\SessionInterface;
use Symfony\Component\HttpKernel\Event\GetResponseEvent;
use Symfony\Component\HttpKernel\HttpKernelInterface;
use Symfony\Component\HttpKernel\KernelEvents;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;

class SessionListener implements EventSubscriberInterface
{
    /** @var Connection */
    protected $conn;

    /** @var EntityManager */
    protected $em;

    protected $router;

    /**
     * @var ContainerInterface
     */
    protected $container;

    /**
     * @var AwTokenStorageInterface
     */
    private $tokenStorage;
    /**
     * @var AuthorizationCheckerInterface
     */
    private $authorizationChecker;

    /** @var LoggerInterface */
    private $logger;

    /** @var Geo */
    private $globalsGeo;

    /** @var bool */
    private $isDebug;

    /**
     * @var RequestStack
     */
    private $requestStack;

    public function __construct(EntityManager $em,
        ?AwTokenStorageInterface $tokenStorage = null,
        ?AuthorizationCheckerInterface $authorizationChecker = null,
        Router $router,
        ContainerInterface $container,
        LoggerInterface $logger,
        $isDebug,
        RequestStack $requestStack
    ) {
        $this->conn = $em->getConnection();
        $this->em = $em;
        $this->tokenStorage = $tokenStorage;
        $this->authorizationChecker = $authorizationChecker;
        $this->router = $router;
        $this->container = $container;
        $this->logger = $logger;
        $this->globalsGeo = $this->container->get('aw.globals.geo');
        $this->isDebug = $isDebug;
        $this->requestStack = $requestStack;
    }

    public function saveRememberMeId(int $rememberMeId)
    {
        $request = $this->requestStack->getMasterRequest();
        $userId = $this->getUserId();

        if (!empty($userId)) {
            $this->saveSession($request->getSession(), $request, $userId, $rememberMeId, true);
        }
    }

    public function onKernelRequest(GetResponseEvent $event)
    {
        if (!$event->isMasterRequest()) {
            return;
        }

        $request = $event->getRequest();

        if (!$request->hasSession()) {
            return;
        }

        if (
            $this->tokenStorage
            && ($token = $this->tokenStorage->getToken())
            && (($sessionUser = $token->getUser()) instanceof Usr)
            && $this->container->has(AppProcessor::class)
        ) {
            $this->container->get(AppProcessor::class)->setUserid($sessionUser->getUserid());
        }

        $session = $request->getSession();
        $userId = $this->getUserId();

        if (!$userId || empty($session->getId())) {
            return;
        }

        if ($this->isInvalidSession($session, $request)) {
            $subRequest = $request->duplicate([], [], ["_controller" => 'AwardWallet\MainBundle\Controller\LogoutController::logoutAction']);
            $response = $event->getKernel()->handle($subRequest, HttpKernelInterface::SUB_REQUEST, true);
            $event->setResponse($response);

            return;
        }

        // optimization. load business user from session. was set to Session in AuthenticationListener
        if (
            $this->tokenStorage
            && ($token = $this->tokenStorage->getToken())
            && (($sessionUser = $token->getUser()) instanceof Usr)
            && $session->has('BusinessUserID_' . $sessionUser->getUserid())
        ) {
            $businessId = $session->get('BusinessUserID_' . $sessionUser->getUserid());
            $businessUser = $businessId ? $this->em->getRepository(\AwardWallet\MainBundle\Entity\Usr::class)->find($businessId) : null;

            $this->tokenStorage->setBusinessUser($businessUser);
            $sessionUser->_setBusinessByLevel($businessUser, [ACCESS_ADMIN, ACCESS_BOOKING_MANAGER, ACCESS_BOOKING_VIEW_ONLY]);
        }

        $this->saveSession(
            $session,
            $request,
            $userId,
            $request->attributes->get(RememberMeServices::REQUEST_ATTR_TOKEN_ID)
        );

        // renew old format passwords
        $this->container->get('aw.manager.local_passwords_manager')->hasPassword(0);
    }

    public function saveSession(SessionInterface $session, Request $request, $userId, ?int $rememberMeTokenId = null, bool $forceUpdate = false)
    {
        if (empty($session->getId())) {
            return null;
        }

        if (
            $forceUpdate
            || ((time() - $session->get('sessLastSaveTime', 0)) > 60)
        ) {
            $isMobile = (new RequestMatcher('^/m/api/'))->matches($request);

            if ($isMobile && $request->headers->has(MobileHeaders::MOBILE_VERSION)) {
                $ua = "Mobile App (" . $request->headers->get(MobileHeaders::MOBILE_PLATFORM, 'browser') . " " . $request->headers->get(MobileHeaders::MOBILE_VERSION) . ")";
            } else {
                $ua = $request->headers->get('user_agent', 'unknown');
            }

            $this->conn->executeUpdate("
                INSERT INTO
                  Session (SessionID, UserID, IP, UserAgent, Valid, LoginDate, LastActivityDate" . (isset($rememberMeTokenId) ? ', RememberMeTokenID' : '') . ")
                  VALUES (:id, :user, :ip, :ua, 1, :time, :time" . (isset($rememberMeTokenId) ? ', :tokenid' : '') . ")
                ON DUPLICATE KEY UPDATE
                  IP = VALUES(IP), UserAgent = VALUES(UserAgent), LastActivityDate = VALUES(LastActivityDate)" . (isset($rememberMeTokenId) ? ', RememberMeTokenID = VALUES(RememberMeTokenID)' : ''),
                \array_merge(
                    [
                        "id" => $session->getId(),
                        "user" => $userId,
                        "ip" => $request->getClientIp(),
                        "ua" => $ua,
                        "time" => date("Y-m-d H:i:s"),
                    ],
                    isset($rememberMeTokenId) ? ["tokenid" => $rememberMeTokenId] : []
                )
            );

            $session->set('sessLastSaveTime', time());
        }
    }

    public function groupSessions(array $data, ?string $currentSessId = null): array
    {
        if (empty($data)) {
            return $data;
        }

        $result = [];

        foreach ($data as $item) {
            $item['getBrowser'] = UserAgentUtils::getBrowser($item['UserAgent']);

            if (array_key_exists($item['_ts'], $result)) {
                $indx = 0;

                while (++$indx < 100) {
                    if (!array_key_exists($indx + $item['_ts'], $result)) {
                        $result[$indx + $item['_ts']] = $item;

                        break;
                    }
                }
            } else {
                $result[$item['_ts']] = $item;
            }
        }
        krsort($result, SORT_NUMERIC);
        $data = $result;

        $filtered = [];

        foreach ($data as $tstamp => $item) {
            $existKey = md5($item['IP'] . $item['getBrowser']['platform'] . $item['getBrowser']['browser']);

            if (!empty($item['SessionID']) && $currentSessId === $item['SessionID']) {
                array_key_exists($existKey, $filtered)
                    ? $filtered[$existKey]['_current'] = true
                    : $item['_current'] = true;
            }
            $item['LastUsed'] = new \DateTime($item['LastUsed']);
            $item['location'] = empty($item['IP']) ? '' : ($this->globalsGeo->getLocationByIp($item['IP'])['name'] ?? '');

            if (array_key_exists($existKey, $filtered)) {
                $filtered[$existKey]['_group']['UserAgent'][] = $item['UserAgent'];

                if (!empty($item['SessionID'])) {
                    $filtered[$existKey]['_group']['SessionID'][] = $item['SessionID'];
                }

                if (!empty($item['RememberMeTokenID'])) {
                    $filtered[$existKey]['_group']['RememberMeTokenID'][] = $item['RememberMeTokenID'];
                }
            } else {
                $filtered[$existKey] = $item;
            }
        }

        return $filtered;
    }

    public function fetchOldSessions(int $userId): array
    {
        $expiredActivity = intval(ini_get('session.gc_maxlifetime')) + (30 * 60);

        $session = $this->conn->fetchAll('
            SELECT SessionID, RememberMeTokenID, IP, UserAgent, LastActivityDate AS LastUsed, UNIX_TIMESTAMP(LastActivityDate) as _ts
            FROM `Session`
            WHERE
                    UserID = ?
                AND (
                        Valid = 0
                    OR  UNIX_TIMESTAMP() - UNIX_TIMESTAMP(LastActivityDate) > ?
                )',
            [$userId, $expiredActivity],
            [\PDO::PARAM_INT, \PDO::PARAM_INT]
        );

        return $session;
    }

    public function detectOtherSessions($currentSessionId, $currentUserId, $currentIp)
    {
        return $this->conn->fetchAll(
            'SELECT DISTINCT IP FROM Session WHERE SessionID <> ? AND UserID = ? AND IP <> ? AND Valid = 1',
            [$currentSessionId, $currentUserId, $currentIp],
            [\PDO::PARAM_STR, \PDO::PARAM_INT, \PDO::PARAM_STR]
        );
    }

    /**
     * @param int $userId
     * @param string $currentIp
     * @param string $ua
     */
    public function invalidateUserSessionsByIp($userId, $currentIp, $ua = '')
    {
        $uaq = $ua ? 'AND UserAgent = :ua' : '';
        $stmt = $this->conn->prepare("
              UPDATE
                Session
              SET
                Valid = 0
              WHERE
              	IP = :ip
                AND Valid = 1
                AND UserID = :uid
                {$uaq}
            ");
        $stmt->bindParam(':uid', $userId, \PDO::PARAM_INT);
        $stmt->bindParam(':ip', $currentIp, \PDO::PARAM_INT);

        if ($ua) {
            $stmt->bindParam(':ua', $ua);
        }
        $stmt->execute();
    }

    /**
     * @param int $userId
     * @param string $currentSession
     */
    public function invalidateUserSessionsButCurrent($userId, $currentSession)
    {
        $stmt = $this->conn->prepare("
              UPDATE
                Session
              SET
                Valid = 0
              WHERE
              	SessionID <> :session
                AND Valid = 1
                AND UserID = :uid
            ");

        $stmt->bindParam(':session', $currentSession, \PDO::PARAM_STR);
        $stmt->bindParam(':uid', $userId, \PDO::PARAM_INT);
        $stmt->execute();
    }

    /**
     * @throws \Doctrine\DBAL\DBALException
     */
    public function invalidateUserSession(int $userId, string $sessionId): int
    {
        return $this->conn->executeUpdate('
            UPDATE `Session`
            SET Valid = 0
            WHERE
                    SessionID = :sid
                AND UserID    = :uid
                AND Valid     = 1
            LIMIT 1',
            ['sid' => $sessionId, 'uid' => $userId],
            [\PDO::PARAM_STR, \PDO::PARAM_INT]
        );
    }

    public function invalidateUserSessionByRememberTokenId(int $userId, string $rememberTokenId): int
    {
        $this->deleteByRememberMeTokenId($userId, $rememberTokenId);

        return $this->conn->executeUpdate('
            UPDATE `Session`
            SET Valid = 0
            WHERE
                    UserID = :uid
                AND Valid  = 1
                AND RememberMeTokenID = :tokenid
            LIMIT 1',
            ['uid' => $userId, 'tokenid' => $rememberTokenId],
            [\PDO::PARAM_INT, \PDO::PARAM_INT]
        );
    }

    public function invalidateUserSessionByIpUaButCurrent(int $userId, $ip, $ua, string $currentSessionId)
    {
        return $this->conn->executeUpdate('
            UPDATE `Session`
            SET Valid = 0
            WHERE
                    UserID     = :uid
                AND SessionID <> :sid
                AND Valid      = 1
                AND IP         = :ip
                AND UserAgent  = :ua
            ',
            ['sid' => $currentSessionId, 'uid' => $userId, 'ip' => $ip, 'ua' => $ua],
            [\PDO::PARAM_STR, \PDO::PARAM_INT, \PDO::PARAM_STR, \PDO::PARAM_STR]
        );
    }

    public function invalidateAllButCurrent(int $userId, string $sessionId): bool
    {
        if ($this->authorizationChecker->isGranted('USER_IMPERSONATED')) {
            throw new ImpersonatedException();
        }

        $this->container->get('monolog.logger.security')->warning('removing all other sessions');

        $this->invalidateUserSessionsButCurrent($userId, $sessionId);
        $this->conn->executeUpdate(
            'DELETE FROM MobileDevice WHERE UserID = ?',
            [$userId], [\PDO::PARAM_INT]);

        $tokenId = $this->conn->fetchColumn(
            'SELECT RememberMeTokenID FROM Session WHERE SessionID = ? AND UserID = ?',
            [$sessionId, $userId], 0);
        $this->conn->executeUpdate(
            'DELETE FROM RememberMeToken WHERE UserID = ? AND RememberMeTokenID <> ' . (empty($tokenId) ? 0 : $tokenId),
            [$userId], [\PDO::PARAM_INT, \PDO::PARAM_INT]);

        return true;
    }

    public static function getSubscribedEvents()
    {
        return [
            KernelEvents::REQUEST => ['onKernelRequest', 7],
        ];
    }

    /**
     * @return int|null
     */
    private function getUserId()
    {
        if (!$this->tokenStorage || !$this->tokenStorage->getToken() || !$this->tokenStorage->getToken()->getUser() instanceof Usr || Utils::tokenHasRole($this->tokenStorage->getToken(), 'ROLE_IMPERSONATED_ANY')) {
            return null;
        }

        return $this->tokenStorage->getToken()->getUser()->getUserid();
    }

    /**
     * @param Session $session
     * @return bool
     * @throws \Doctrine\DBAL\DBALException
     */
    private function isInvalidSession(SessionInterface $session, Request $request)
    {
        $sessionId = $session->getId();
        $user = $this->tokenStorage->getUser();

        if ($user instanceof Usr) {
            $isUaChanged = $this->isSessionUserAgentChanged($session, $request);
            $isLocationChanged = $this->isSessionLocationChanged($user, $session, $request);

            if ($isUaChanged && $isLocationChanged) {
                $this->logger->critical('User IP, UserAgent changed - logout', [
                    'UserID' => $user->getUserid(),
                    'IP' => $request->getClientIp(),
                    'LogonIP' => $session->get('LogonIP'),
                    'UserAgent' => $request->headers->get('User-Agent'),
                    'LogonUserAgent' => $session->get('LogonUserAgent'),
                ]);
                $this->invalidateUserSession($user->getUserid(), $sessionId);

                if (!empty($tokenId = $session->get("RememberMeTokenID"))) {
                    $this->deleteByRememberMeTokenId($user->getUserid(), $tokenId);
                }

                return true;
            }
        }

        $stmt = $this->conn->prepare("SELECT 1 FROM Session WHERE SessionID = :id AND Valid = 0");
        $stmt->bindParam(':id', $sessionId, \PDO::PARAM_STR);
        $stmt->execute();

        if ($stmt->fetch()) {
            return true;
        }

        return false;
    }

    /**
     * @param Session $session
     * @return bool
     */
    private function isSessionUserAgentChanged(SessionInterface $session, Request $request)
    {
        $currentUA = $request->headers->get('User-Agent');

        $result = $session->get('UserAgent') != $currentUA && UserAgentUtils::filterUserAgent($session->get("LogonUserAgent")) !== UserAgentUtils::filterUserAgent($currentUA);

        if (!$result) {
            $session->set("UserAgent", $currentUA);
        }

        return $result;
    }

    private function isSessionLocationChanged(Usr $user, SessionInterface $session, Request $request)
    {
        $currentIp = $request->getClientIp();

        if ($session->get("IP") == $currentIp) {
            return false;
        }

        if ($this->authorizationChecker->isGranted('ROLE_STAFF') && !$this->globalsGeo->isUserMatchLocations($user, $currentIp, $session->get("LogonIP"))) {
            return true;
        }

        $session->set("IP", $currentIp);

        return false;
    }

    private function deleteByRememberMeTokenId(int $userId, string $rememberTokenId): int
    {
        return $this->conn->executeUpdate(
            'DELETE FROM RememberMeToken WHERE UserID = ? AND RememberMeTokenID = ?',
            [$userId, $rememberTokenId],
            [\PDO::PARAM_INT, \PDO::PARAM_INT]);
    }
}
