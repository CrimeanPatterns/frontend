<?php

namespace AwardWallet\MainBundle\Service\CreditCards;

use AwardWallet\MainBundle\Entity\CreditCard;
use AwardWallet\MainBundle\Repository\CreditCardRepository;
use AwardWallet\MainBundle\Service\Cache\CacheManager;
use AwardWallet\MainBundle\Service\Cache\Model\CacheItemReference;
use AwardWallet\MainBundle\Service\Cache\Tags;
use Psr\Log\LoggerInterface;

class CreditCardPatternsSource
{
    private const PATTERNS_MEMCACHED_KEY = "credit_cards_detect_patterns_v3";
    private const CACHE_KEY_EXPIRATION = 300;

    private CreditCardRepository $cardRepository;
    private CacheManager $cacheManager;
    private LoggerInterface $logger;
    private PatternsParser $patternsParser;

    public function __construct(CreditCardRepository $cardRepository, CacheManager $cacheManager, LoggerInterface $logger, PatternsParser $patternsParser)
    {
        $this->cardRepository = $cardRepository;
        $this->cacheManager = $cacheManager;
        $this->logger = $logger;
        $this->patternsParser = $patternsParser;
    }

    /**
     * @return array - [<ProviderID1> => [<CreditCardID1> => ['Patterns' => ['pattern1', '#pattern2#ims'], 'MatchingOrder' => 100], .../
     */
    public function getPatterns(): array
    {
        $cacheItem = new CacheItemReference(
            self::PATTERNS_MEMCACHED_KEY,
            Tags::addTagPrefix([Tags::TAG_CREDIT_CARDS_INFO]),
            function () {
                return $this->loadCreditCards();
            }
        );

        return $this->cacheManager->load($cacheItem->setExpiration(self::CACHE_KEY_EXPIRATION));
    }

    private function loadCreditCards(): array
    {
        $this->logger->info("loading credit cards");
        $cards = $this->cardRepository->findBy([], ['provider' => 'ASC', 'matchingOrder' => 'ASC']);

        $result = [];

        /** @var CreditCard $card */
        foreach ($cards as $card) {
            $providerId = $card->getProvider()->getId();

            if (empty(trim($card->getPatterns()))) {
                continue;
            }

            if (!isset($result[$providerId])) {
                $result[$providerId] = [];
            }

            $result[$providerId][$card->getId()] = [
                'Patterns' => $this->patternsParser->parse($card->getPatterns()),
                'MatchingOrder' => $card->getMatchingOrder(),
            ];
        }

        $this->logger->info("loaded " . count($result) . " cc providers");

        return $result;
    }
}
