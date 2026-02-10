<?php

namespace AwardWallet\MainBundle\Service\MileValue;

use AwardWallet\MainBundle\Entity\CreditCard;
use AwardWallet\MainBundle\Entity\Provider;
use AwardWallet\MainBundle\Service\AccountHistory\AnalyserContextFactory;
use AwardWallet\MainBundle\Service\AccountHistory\Context;
use Doctrine\DBAL\Connection;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;

class MileValueCards
{
    public const COST_CASHBACK_USD = 1;
    public const COST_CASHBACK_POINT = 0.01;
    private LoggerInterface $logger;
    private EntityManagerInterface $entityManager;
    private MileValueService $mileValueService;
    private MileValueCache $mileValueCache;
    private Connection $connection;
    private AnalyserContextFactory $contextFactory;
    private $cacheContext;
    private array $cards = [];

    public function __construct(
        LoggerInterface $logger,
        EntityManagerInterface $entityManager,
        MileValueService $mileValueService,
        MileValueCache $mileValueCache,
        AnalyserContextFactory $contextFactory
    ) {
        $this->logger = $logger;
        $this->entityManager = $entityManager;
        $this->mileValueService = $mileValueService;
        $this->mileValueCache = $mileValueCache;
        $this->contextFactory = $contextFactory;

        $this->connection = $entityManager->getConnection();
    }

    public function getCards(): array
    {
        if (empty($this->cards)) {
            $cards = $this->connection->fetchAllAssociative('
                SELECT CreditCardID, ProviderID, CobrandProviderID, IsCashBackOnly, CashBackType
                FROM CreditCard
            ');

            $this->cards = array_column($cards, null, 'CreditCardID');
        }

        return $this->cards;
    }

    /**
     * @param CreditCard|int $cardOrId
     */
    public function getCardMileValueCost($cardOrId, ?Context $context = null): MileValueCost
    {
        $cardId = $cardOrId instanceof CreditCard ? $cardOrId->getId() : $cardOrId;
        $cards = $context ? $context->getAllCardsAssociative()->getValue() : $this->getCards();

        if (!array_key_exists($cardId, $cards)) {
            return new MileValueCost(null);
        }

        $card = $cards[$cardId];
        $isCashBackOnly = (bool) $card['IsCashBackOnly'];
        $cobrandProviderId = (int) $card['CobrandProviderID'];

        if ($isCashBackOnly && 0 === $cobrandProviderId) {
            $cashBackType = CreditCard::CASHBACK_TYPE_POINT === (int) $card['CashBackType']
                ? CreditCard::CASHBACK_TYPE_POINT
                : CreditCard::CASHBACK_TYPE_USD;
            $costValue = CreditCard::CASHBACK_TYPE_POINT === $cashBackType
                ? self::COST_CASHBACK_POINT
                : self::COST_CASHBACK_USD;

            return new MileValueCost($costValue, null, null, true, (int) $card['CobrandProviderID'], $cashBackType);
        }

        $providerId = $cobrandProviderId ?: $card['ProviderID'];

        if (Provider::AIRFRANCE_ID === $providerId) {
            $providerId = Provider::KLM_ID;
        }

        $mileValueItem = $context
            ? ($context->getMileValueAvailableProvidersMap()[$providerId] ?? null)
            : ($this->getMileValueFlatList()[$providerId] ?? null);

        if (null === $mileValueItem) {
            return new MileValueCost(null);
        }

        return new MileValueCost(
            $mileValueItem->getPrimaryValue(MileValueService::PRIMARY_CALC_FIELD),
            $mileValueItem->getMinValue(MileValueService::PRIMARY_CALC_FIELD),
            $mileValueItem->getMaxValue(MileValueService::PRIMARY_CALC_FIELD),
            $isCashBackOnly,
            (int) $card['CobrandProviderID']
        );
    }

    public function getCashBackCostHundred(CreditCard $creditCard): float
    {
        $mileValueCost = $this->getCardMileValueCost($creditCard);
        $cost = $mileValueCost->getPrimaryValue();

        if (null === $creditCard->getCobrandProvider()) {
            $cost *= 100;
        }

        return $cost;
    }

    private function getMileValueFlatList(): array
    {
        if (null === $this->cacheContext) {
            $this->cacheContext = $this->contextFactory->makeCacheContext();
        }

        return $this->cacheContext->getMileValueAvailableProvidersMap()->getValue();
    }
}
