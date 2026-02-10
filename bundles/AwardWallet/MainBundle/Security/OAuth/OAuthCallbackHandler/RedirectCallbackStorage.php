<?php

namespace AwardWallet\MainBundle\Security\OAuth\OAuthCallbackHandler;

use Psr\Log\LoggerInterface;

class RedirectCallbackStorage
{
    /**
     * @var \Memcached
     */
    private $memcached;
    /**
     * @var LoggerInterface
     */
    private $logger;

    public function __construct(
        LoggerInterface $logger,
        \Memcached $memcached
    ) {
        $this->memcached = $memcached;
        $this->logger = $logger;
    }

    public function save(string $code, array $data): void
    {
        $this->memcached->set(self::getCacheKey($code), $data, 180);
    }

    public function load(string $code): ?array
    {
        $data = $this->memcached->get(self::getCacheKey($code));

        if (!\is_array($data)) {
            $this->logger->warning("no cache for post redirect, transfer to home page");

            return null;
        }

        return $data;
    }

    protected static function getCacheKey(string $code): string
    {
        return 'oauth_key_apple_mobile_' . $code;
    }
}
