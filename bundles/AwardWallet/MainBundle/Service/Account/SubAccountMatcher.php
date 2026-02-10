<?php

namespace AwardWallet\MainBundle\Service\Account;

use AwardWallet\MainBundle\Globals\StringHandler;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;

class SubAccountMatcher
{
    public const CACHE_KEY = 'SubAccountBlogPosts';
    public const CACHE_LIFETIME = 86400;

    private LoggerInterface $logger;
    private EntityManagerInterface $entityManager;
    private \Memcached $memcached;

    private array $patterns = [];

    public function __construct(LoggerInterface $logger, EntityManagerInterface $entityManager, \Memcached $memcached)
    {
        $this->logger = $logger;
        $this->entityManager = $entityManager;
        $this->memcached = $memcached;
    }

    public function fetchPostIds(int $providerId, string $name, string $field): array
    {
        foreach ($this->getPatternsByProvider($providerId) as $pattern) {
            if (!empty($pattern[$field]) && $this->isMatch($name, $pattern)) {
                return $pattern[$field];
            }
        }

        return [];
    }

    private function isMatch(string $name, array $pattern): bool
    {
        if ($pattern['isRegex']) {
            $pattern['pattern'] .= 'is'; // append modificators

            return 1 === preg_match($pattern['pattern'], $name);
        }

        return false !== stripos($name, $pattern['pattern']);
    }

    private function getPatternsByProvider(int $providerId): array
    {
        return $this->getPatterns()[$providerId] ?? [];
    }

    private function getPatterns(): array
    {
        if (!empty($this->patterns)) {
            return $this->patterns;
        }

        $patterns = $this->memcached->get(self::CACHE_KEY);

        if (!empty($patterns)) {
            return $patterns;
        }

        $subAccountTypes = $this->entityManager->getConnection()->fetchAllAssociative('
            SELECT ProviderID, Patterns, BlogIds
            FROM SubAccountType 
            WHERE
                    Patterns IS NOT NULL
                AND BlogIds IS NOT NULL
            ORDER BY -MatchingOrder DESC, LENGTH(Patterns) DESC
        ');

        foreach ($subAccountTypes as $type) {
            if (!array_key_exists($type['ProviderID'], $this->patterns)) {
                $this->patterns[$type['ProviderID']] = [];
            }

            $patterns = explode("\n", trim($type['Patterns']));

            $matcher = [];

            foreach ($patterns as $pattern) {
                $pattern = trim($pattern);
                $matcher[] = [
                    'pattern' => $pattern,
                    'isRegex' => '#' === $pattern[0] && '#' === $pattern[-1],
                    'BlogIds' => StringHandler::getIntArrayFromString($type['BlogIds']),
                ];
            }

            $this->patterns[$type['ProviderID']] = array_merge($this->patterns[$type['ProviderID']], $matcher);
        }

        $this->memcached->set(self::CACHE_KEY, $this->patterns, self::CACHE_LIFETIME);

        return $this->patterns;
    }
}
