<?php

namespace AwardWallet\MainBundle\Service\OneTimeCodeProcessor;

use AwardWallet\Common\OneTimeCode\ProviderQuestionAnalyzer;
use AwardWallet\MainBundle\Entity\Usr;
use Psr\Log\LoggerInterface;

class EmailProcessor
{
    private AccountFinder $finder;

    private OtcCache $cache;

    private OTCProcessor $otcProcessor;

    private LoggerInterface $logger;

    public function __construct(
        AccountFinder $finder,
        OtcCache $cache,
        OTCProcessor $otcProcessor,
        LoggerInterface $logger)
    {
        $this->finder = $finder;
        $this->cache = $cache;
        $this->otcProcessor = $otcProcessor;
        $this->logger = $logger;
    }

    public function process(string $providerCode, Usr $user, string $code): bool
    {
        if (!ProviderQuestionAnalyzer::isProviderOtc($providerCode)) {
            return false;
        }

        if ($this->cache->hasCodeCollision($user->getId(), $providerCode)) {
            $this->logger->info('otc: collision detected, skipping', ['userId' => $user->getId(), 'provider' => $providerCode]);

            return false;
        }

        if ($this->cache->hasStop($user->getId(), $providerCode)) {
            $this->logger->info('otc: stop signal detected, skipping', ['userId' => $user->getId(), 'provider' => $providerCode]);

            return false;
        }

        if ($this->cache->getProviderOtc($user->getId(), $providerCode)) {
            $this->logger->info('otc: collision', ['userId' => $user->getId(), 'provider' => $providerCode]);
            $this->cache->setCodeCollision($user->getId(), $providerCode);

            return false;
        }
        $found = $this->finder->find($user, $providerCode);

        if (count($found->candidates) > 1) {
            $this->logger->info('otc: multiple candidates found ' . implode(',', $found->candidates), ['userId' => $user->getId(), 'provider' => $providerCode]);
            $this->cache->setCodeCollision($user->getId(), $providerCode);

            return false;
        }

        if (count($found->candidates) == 1) {
            $this->cache->setProviderOtc($user->getId(), $providerCode, $code);
            $this->logger->info('otc: received code for candidate ' . implode(',', $found->candidates), ['userId' => $user->getId(), 'provider' => $providerCode]);

            if (!empty($found->found)) {
                $this->logger->info('otc: found matching account', ['userId' => $user->getId(), 'provider' => $providerCode, 'accountId' => $found->found->getId()]);
                $this->otcProcessor->process($found->found);
            }

            return true;
        }
        $this->logger->info('otc: no possible candidates found', ['userId' => $user->getId(), 'provider' => $providerCode]);

        return false;
    }
}
