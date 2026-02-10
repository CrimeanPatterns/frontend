<?php

namespace AwardWallet\MainBundle\Updater;

use AwardWallet\MainBundle\Globals\LoggerContext\ContextAwareLoggerWrapper;
use AwardWallet\MainBundle\Globals\StringUtils;
use Psr\Log\LoggerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpFoundation\Session\SessionInterface;

use function Duration\minutes;

class EventsChannelMigrator
{
    private const EVENTS_CHANNEL_MIGRATOR_SESSION_KEY = 'updater_events_channel_migrator';
    private \Memcached $memcached;
    private ContextAwareLoggerWrapper $logger;
    private RequestStack $requestStack;

    public function __construct(
        \Memcached $memcached,
        LoggerInterface $securityLogger,
        RequestStack $requestStack
    ) {
        $this->memcached = $memcached;
        $this->logger = (new ContextAwareLoggerWrapper($securityLogger))
            ->setTypedContext(true)
            ->withClass(self::class);
        $this->requestStack = $requestStack;
    }

    public function send(string $sessionKey): string
    {
        $token = StringUtils::getRandomCode(40);
        $this->memcached->set(
            self::generateKeyFromToken($token),
            [UpdaterSessionManager::createSessionChannelName($sessionKey), $token],
            minutes(5)->getAsSecondsInt()
        );
        $this->logger->info('Token has been stored');

        return $token;
    }

    public function isJustMigrated(): bool
    {
        $session = $this->grabSession();

        if (!$session) {
            return false;
        }

        return $session->has(self::EVENTS_CHANNEL_MIGRATOR_SESSION_KEY);
    }

    public function receive(string $token, Request $request): bool
    {
        $channelData = $this->memcached->get(self::generateKeyFromToken($token));

        if ($this->memcached->getResultCode() !== \Memcached::RES_SUCCESS) {
            $this->logger->info('Token not found in storage');

            return false;
        }

        $session = $request->getSession();

        if (!$session->isStarted()) {
            $this->logger->info('Session is not started');

            return false;
        }

        $session->set(self::EVENTS_CHANNEL_MIGRATOR_SESSION_KEY, $channelData);
        $this->logger->info('Channel data has been migrated to session');

        return true;
    }

    public function validate(string $userProvidedChannelName): bool
    {
        $session = $this->grabSession();

        if (!$session) {
            return false;
        }

        $sessionChannelData = $session->get(EventsChannelMigrator::EVENTS_CHANNEL_MIGRATOR_SESSION_KEY);

        try {
            if (
                !\is_array($sessionChannelData)
                || !\array_is_list($sessionChannelData)
                || (\count($sessionChannelData) !== 2)
            ) {
                $this->logger->info('Invalid channel data format');

                return false;
            }

            [$sessionChannelName, $token] = $sessionChannelData;
            $isValid =
                StringUtils::isNotEmpty($sessionChannelName)
                && \hash_equals($sessionChannelName, $userProvidedChannelName);
            $this->logger->info('Channel name validation result', ['isValid' => $isValid]);
            $this->memcached->delete(self::generateKeyFromToken($token));

            return $isValid;
        } finally {
            $session->remove(EventsChannelMigrator::EVENTS_CHANNEL_MIGRATOR_SESSION_KEY);
        }
    }

    private function grabSession(): ?SessionInterface
    {
        $request = $this->requestStack->getCurrentRequest();

        if (!$request) {
            return null;
        }

        $session = $request->getSession();

        if (!$session->isStarted()) {
            $this->logger->info('Session is not started');

            return null;
        }

        return $session;
    }

    private static function generateKeyFromToken(string $token): string
    {
        return 'updater_events_channel_migration_' . $token;
    }
}
