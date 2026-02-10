<?php

namespace AwardWallet\MainBundle\Service\AccountHistory\MerchantRecentTransactionsStatLoader;

use AwardWallet\MainBundle\Entity\CreditCard;
use AwardWallet\MainBundle\Service\AccountHistory\BankTransactionsAnalyser;
use AwardWallet\MainBundle\Service\AccountHistory\OfferCreditCardItem;
use AwardWallet\MainBundle\Service\MileValue\MileValueCards;
use AwardWallet\MainBundle\Service\MileValue\MileValueService;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;

class MerchantRecentTransactionsStatLoader
{
    private const MINIMUM_MULTIPLIER_THRESHOLD = 1.5;
    // Transactions threshold with minimum multiplier
    private const MINIMUM_TRANSACTIONS_COUNT_THRESHOLD = 0.5;

    private MerchantRecentTransactionsStatQuery $merchantRecentTransactionsStatQuery;
    private EntityManagerInterface $entityManager;
    private MileValueService $mileValueService;
    private LoggerInterface $logger;
    private BankTransactionsAnalyser $bankTransactionsAnalyser;
    private MileValueCards $mileValueCards;

    public function __construct(
        MerchantRecentTransactionsStatQuery $merchantRecentTransactionsStatQuery,
        EntityManagerInterface $entityManager,
        MileValueService $mileValueService,
        LoggerInterface $logger,
        BankTransactionsAnalyser $bankTransactionsAnalyser,
        MileValueCards $mileValueCards
    ) {
        $this->merchantRecentTransactionsStatQuery = $merchantRecentTransactionsStatQuery;
        $this->entityManager = $entityManager;
        $this->mileValueService = $mileValueService;
        $this->logger = $logger;
        $this->bankTransactionsAnalyser = $bankTransactionsAnalyser;
        $this->mileValueCards = $mileValueCards;
    }

    /**
     * @param ?callable(Stat): bool $cardExistsInOfferPredicate
     * @param ?callable(CreditCard): bool $userHasCardPredicate
     * @return array<int, OfferCreditCardItem[]>
     */
    public function load(
        array $merchantIds,
        int $minMerchantTransactions,
        ?callable $cardExistsInOfferPredicate = null,
        ?callable $userHasCardPredicate = null,
        ?\DateTime $startDate = null,
        array $filterIds = []
    ): array {
        if (!$startDate) {
            $startDate = new \DateTime('first day of -3 month');
            $startDate->setTime(0, 0, 0);
        }

        $merchantToStatMap = $this->merchantRecentTransactionsStatQuery->execute(
            $merchantIds,
            $minMerchantTransactions,
            $startDate
        );

        $cardTransactionsCountMap = [];
        $cardMultipliersDataMap = [];

        foreach ($merchantToStatMap as $merchantId => $merchantMultipliers) {
            foreach ($merchantMultipliers as $merchantMultiplier) {
                $cardId = $merchantMultiplier->creditCardId;

                if (!empty($filterIds) && !in_array($cardId, $filterIds)) {
                    continue;
                }

                if ($cardExistsInOfferPredicate && $cardExistsInOfferPredicate($merchantMultiplier)) {
                    continue;
                }

                if (!isset($cardTransactionsCountMap[$merchantId][$cardId])) {
                    $cardTransactionsCountMap[$merchantId][$cardId] = 0;
                }

                $cardTransactionsCountMap[$merchantId][$cardId] += $merchantMultiplier->transactions;

                if (!isset($cardMultipliersDataMap[$merchantId][$cardId])) {
                    $cardMultipliersDataMap[$merchantId][$cardId] = [];
                }

                $cardMultipliersDataMap[$merchantId][$cardId][] = [
                    $merchantMultiplier->multiplier,
                    $merchantMultiplier->transactions,
                ];
            }
        }

        $resultMap = [];

        foreach ($cardTransactionsCountMap as $merchantId => $merchantCardTransactionsCountMap) {
            $merchantResult = [];

            foreach ($merchantCardTransactionsCountMap as $cardId => $allTransactions) {
                foreach ($cardMultipliersDataMap[$merchantId][$cardId] as [$multiplier, $multiplierTransactions]) {
                    if ($multiplier < self::MINIMUM_MULTIPLIER_THRESHOLD) {
                        continue 2;
                    }

                    if ($multiplierTransactions / $allTransactions < self::MINIMUM_TRANSACTIONS_COUNT_THRESHOLD) {
                        continue;
                    }

                    /** @var CreditCard $cardEntity */
                    $cardEntity = $this->entityManager->getRepository(CreditCard::class)->find($cardId);

                    if (
                        $cardEntity->isDiscontinued()
                        && $userHasCardPredicate
                        && !$userHasCardPredicate($cardEntity)
                    ) {
                        continue 2;
                    }

                    // $mileValueItem = $this->mileValueService->getMileValueViaCreditCardId($cardEntity);
                    $mileValueItem = $this->mileValueCards->getCardMileValueCost($cardEntity);
                    $mileValue = $mileValueItem->getPrimaryValue();

                    if (empty($mileValue)) {
                        $this->logger->warning("missing mile value for credit card", ["CreditCardID" => $cardId]);

                        continue 2;
                    }

                    $merchantResult[] = new OfferCreditCardItem(
                        $cardId,
                        $cardEntity->getName(),
                        $multiplier,
                        $mileValue,
                        false,
                        \sprintf('%sx %s', $multiplier, $cardEntity->getPointName()),
                        $cardEntity->getClickURL(),
                        $cardEntity->getPicturePath('medium'),
                        $userHasCardPredicate && $userHasCardPredicate($cardEntity),
                        $cardEntity,
                        $mileValueItem->getMinValue(),
                        $mileValueItem->getMaxValue()
                    );

                    continue 2;
                }
            }

            $resultMap[$merchantId] = $this->bankTransactionsAnalyser->sortOfferCreditCards($merchantResult);
        }

        return $resultMap;
    }
}
