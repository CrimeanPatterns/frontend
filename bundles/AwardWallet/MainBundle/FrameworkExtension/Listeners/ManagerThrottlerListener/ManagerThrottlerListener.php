<?php

namespace AwardWallet\MainBundle\FrameworkExtension\Listeners\ManagerThrottlerListener;

use AwardWallet\MainBundle\Entity\Usr;
use AwardWallet\MainBundle\FrameworkExtension\AwTokenStorageInterface;
use AwardWallet\MainBundle\FrameworkExtension\Exceptions\UserErrorException;
use AwardWallet\MainBundle\Globals\LoggerContext\Context;
use AwardWallet\MainBundle\Globals\LoggerContext\ContextAwareLoggerWrapper;
use AwardWallet\MainBundle\Globals\StringUtils;
use AwardWallet\MainBundle\Security\AntiBruteforceLockerService;
use AwardWallet\MainBundle\Service\AppBot\Adapter\Slack;
use AwardWallet\MainBundle\Service\AppBot\AppBot;
use Clock\ClockInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestMatcher;
use Symfony\Component\HttpFoundation\RequestMatcherInterface;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;

class ManagerThrottlerListener
{
    private const RROMANOV_USER_ID = 61266;
    private const KIBANA_TIME_FORMAT = 'Y-m-d\TH:i:s.000\Z';
    private const EXCLUDED_ROUTES = [
        'email_template_stats' => true,
        'email_template_searchUser' => true,
        'aw_manager_loyalty_hot_session' => true,
        'cache_control_stats' => true,
    ];
    private const EXCLUDED_SCHEMAS = [
        'MileValue' => true,
        'ProviderMileValue' => true,
        'ProviderPhone' => true,
    ];
    private const SCHEMA_ROUTES = [
        'aw_manager_list' => true,
        'aw_manager_list_export' => true,
        'aw_manager_edit' => true,
    ];
    private AntiBruteforceLockerService $throttlerTier1;
    private AntiBruteforceLockerService $throttlerTier2;
    private AntiBruteforceLockerService $throttlerTier3;
    private AntiBruteforceLockerService $throttlerTier4;
    private LoggerInterface $logger;
    private AppBot $notifyBot;
    private AwTokenStorageInterface $tokenStorage;
    private ClockInterface $clock;
    private ManagerLocker $managerLocker;
    private bool $isThrottlingEnabled;
    private AuthorizationCheckerInterface $authorizationChecker;
    /**
     * @var list<array{0: RequestMatcherInterface, 1: ?AntiBruteforceLockerService}>
     */
    private array $matchers = [];

    public function __construct(
        AntiBruteforceLockerService $throttlerTier1,
        AntiBruteforceLockerService $throttlerTier2,
        AntiBruteforceLockerService $throttlerTier3,
        AntiBruteforceLockerService $throttlerTier4,
        AppBot $notifyBot,
        LoggerInterface $logger,
        AwTokenStorageInterface $tokenStorage,
        AuthorizationCheckerInterface $authorizationChecker,
        ClockInterface $clock,
        ManagerLocker $managerLocker,
        bool $isThrottlingEnabled
    ) {
        $this->throttlerTier1 = $throttlerTier1;
        $this->throttlerTier2 = $throttlerTier2;
        $this->throttlerTier3 = $throttlerTier3;
        $this->throttlerTier4 = $throttlerTier4;
        $this->notifyBot = $notifyBot;
        $this->tokenStorage = $tokenStorage;
        $this->managerLocker = $managerLocker;
        $this->isThrottlingEnabled = $isThrottlingEnabled;
        $this->authorizationChecker = $authorizationChecker;
        $this->clock = $clock;
        $this->logger =
            (new ContextAwareLoggerWrapper($logger))
            ->setMessagePrefix('manager throttler listener: ')
            ->pushContext([Context::SERVER_MODULE_KEY => 'manager_throttler_listener'])
            ->withTypedContext();
    }

    public function onKernelRequest(RequestEvent $event): void
    {
        if (!$event->isMasterRequest()) {
            return;
        }

        $user = $this->tokenStorage->getUser();

        if (
            !$user
            || !$this->authorizationChecker->isGranted('ROLE_MANAGE_INDEX')
        ) {
            return;
        }

        if ($this->isThrottlingEnabled && $this->managerLocker->isLocked($user->getId())) {
            $this->throwError($event);
        }

        $request = $event->getRequest();

        if ('aw_oldsite_bootfirewall' === $request->attributes->get('_route')) {
            // recreate request to get correct path from $_SERVER\$request->server
            $server = $request->attributes->get('_original_server');
            $server['SCRIPT_NAME'] = $server['PHP_SELF'] = '/app.php';
            $server['SCRIPT_FILENAME'] = '/www/awardwallet/web/app.php';
            $request = Request::create(
                $server['REQUEST_URI'],
                $request->getMethod(),
                [],
                [],
                [],
                $server
            );
        }

        if (!(new RequestMatcher('^/manager/'))->matches($request)) {
            return;
        }

        /** @var ?AntiBruteforceLockerService $effectiveThrottler */
        $effectiveThrottler = null;

        foreach ($this->getMatchers($user->getId()) as [$matcher, $throttler]) {
            if ($matcher->matches($request)) {
                $effectiveThrottler = $throttler;

                break;
            }
        }

        if (!$effectiveThrottler) {
            return;
        }

        $this->logger->debug('checking path: ' . $request->getRequestUri());

        if (null !== $effectiveThrottler->checkForLockout((string) $user->getId())) {
            if ($this->isThrottlingEnabled) {
                $this->managerLocker->lock($user->getId());
            } else {
                $effectiveThrottler->unlock((string) $user->getId());
            }

            $demoModeNote = $this->isThrottlingEnabled ?
                '' :
                ' (:test_tube:DEMO-MODE:test_tube:)';
            $this->notifyBot->send(
                Slack::CHANNEL_AW_SYSADMIN,
                \sprintf(
                    ":boom::boom::boom:*Manager throttled%s*:boom::boom::boom:\n"
                    . ":ninja:*%s, login: %s* (%s). <%s|requests history> | <%s|[UNLOCK]>",
                    $demoModeNote,
                    $user->getFullName(),
                    $user->getUsername(),
                    $user->getId(),
                    $this->generateRequestsHistoryUrl($user),
                    $this->generateUnlockUrl($user)
                )
            );

            if ($this->isThrottlingEnabled) {
                $this->throwError($event);
            }
        }
    }

    private function throwError(RequestEvent $event)
    {
        $event->stopPropagation();

        throw new UserErrorException('Your account was throttled. Please, contact developers.');
    }

    /**
     * @return list<array<0: RequestMatcherInterface, 1: ?AntiBruteforceLockerService>>
     */
    private function getMatchers(int $userId): array
    {
        if ($this->matchers) {
            return $this->matchers;
        }

        $this->matchers[] = [self::createMultiRouteMatcher(self::EXCLUDED_ROUTES), null];
        $this->matchers[] = [self::createMultiSchemaMatcher(self::EXCLUDED_SCHEMAS), null];

        // no op throttle
        $noThrottleMatchers = [
            new RequestMatcher('^/manager/sonata/email-template'),
            new RequestMatcher('^/manager/reward-availability-status'),
            new RequestMatcher('^/manager/providerStatus\.php'),
            new RequestMatcher('^/manager/operations'),
        ];

        foreach ($noThrottleMatchers as $noThrottleMatcher) {
            $this->matchers[] = [$noThrottleMatcher, null];
        }

        // tier 2, 500 requests per hour
        $tier2Matchers = [
            new RequestMatcher('^/manager/loyalty/logs'),
            new RequestMatcher('^/manager/loyalty/ra-accounts'),
            new RequestMatcher('^/manager/itineraryCheckError'),
            new RequestMatcher('^/manager/account-by-region'),
            new RequestMatcher('^/manager/account-with-ue'),
            new RequestMatcher('^/manager/checkAccount\.php'),
            new RequestMatcher('^/manager/elitelevelcards/'),
        ];

        foreach ($tier2Matchers as $tier2Matcher) {
            $this->matchers[] = [$tier2Matcher, $userId === self::RROMANOV_USER_ID ? $this->throttlerTier4 : $this->throttlerTier2];
        }

        // tier 3, 1000 requests per hour
        $tier3Matchers = [
            new RequestMatcher('^/manager/providerErrors\.php'),
            new RequestMatcher('^/manager/provider\-errors/'),
            new RequestMatcher('^/manager/email/'),
            new RequestMatcher('^/manager/emailadmin/'),
        ];

        foreach ($tier3Matchers as $tier3Matcher) {
            $this->matchers[] = [$tier3Matcher, $userId === self::RROMANOV_USER_ID ? $this->throttlerTier4 : $this->throttlerTier3];
        }

        // tier 1, 250 request per hour, should go last in matchers list because it's accepting all requests
        $this->matchers[] = [
            $this->createAcceptingMatcher(),
            $this->throttlerTier1,
        ];

        return $this->matchers;
    }

    private function generateRequestsHistoryUrl(Usr $user): string
    {
        $userId = $user->getId();
        $login = $user->getLogin();
        $now = $this->clock->current()->getAsDateTimeImmutable();
        $from = $now->modify('-6 hours')->format(self::KIBANA_TIME_FORMAT);
        $to = $now->modify('+1 second')->format(self::KIBANA_TIME_FORMAT);

        return "https://kibana.awardwallet.com/app/discover#/?_g=(filters:!(),refreshInterval:(pause:!t,value:0),time:(from:'{$from}',to:'{$to}'))&_a=(columns:!(path,remote,method),filters:!(),index:f7bcf3e0-1a67-11e9-8067-9bee5e3ddf43,interval:auto,query:(language:kuery,query:'UserID:{$userId}%20and%20path:%2Fmanager*%20AND%20(path:*%20OR%20Login:{$login})'),sort:!())";
    }

    private function generateUnlockUrl(Usr $user): string
    {
        $login = $user->getLogin();

        return "https://jenkins.awardwallet.com/job/Frontend/job/security/job/manager-unlock/parambuild/?userId={$login}";
    }

    private static function createMultiRouteMatcher(array $routesMap): RequestMatcherInterface
    {
        return new class($routesMap) implements RequestMatcherInterface {
            private array $routesMap;

            public function __construct(array $routesMap)
            {
                $this->routesMap = $routesMap;
            }

            public function matches(Request $request)
            {
                $route = $request->attributes->get('_route');

                if (StringUtils::isEmpty($route)) {
                    return false;
                }

                return \array_key_exists($route, $this->routesMap);
            }
        };
    }

    private static function createMultiSchemaMatcher(array $schemaMap): RequestMatcherInterface
    {
        return new class($schemaMap, self::SCHEMA_ROUTES) implements RequestMatcherInterface {
            private array $schemaMap;
            private array $schemaRoutes;

            public function __construct(array $schemaMap, array $schemaRoutes)
            {
                $this->schemaMap = $schemaMap;
                $this->schemaRoutes = $schemaRoutes;
            }

            public function matches(Request $request)
            {
                $route = $request->attributes->get('_route');

                if (StringUtils::isEmpty($route)) {
                    return false;
                }

                if (!\array_key_exists($route, $this->schemaRoutes)) {
                    return false;
                }

                $schema = $request->query->get('Schema');

                if (StringUtils::isEmpty($schema)) {
                    return false;
                }

                return \array_key_exists($schema, $this->schemaMap);
            }
        };
    }

    private function createAcceptingMatcher(): RequestMatcherInterface
    {
        return new class() implements RequestMatcherInterface {
            public function matches(Request $request)
            {
                return true;
            }
        };
    }
}
