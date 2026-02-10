<?php

namespace AwardWallet\MainBundle\Service\AccountHistory;

use AwardWallet\MainBundle\Entity\Merchant;
use AwardWallet\MainBundle\Entity\Usr;

use function AwardWallet\MainBundle\Globals\Utils\iterFluent\it;

class MerchantRecommendationBuilder
{
    private SpentAnalysisService $spentAnalysis;
    private $transactionsExists = [];

    public function __construct(SpentAnalysisService $spentAnalysis)
    {
        $this->spentAnalysis = $spentAnalysis;
    }

    /**
     * @param Merchant[] $merchants
     * @return array<int, array{Merchant, MerchantMobileRecommendation}> merchant to recommendations map
     */
    public function build(array $merchants, Usr $user, Context $context): array
    {
        return
            it($merchants)
            ->reindex(static fn (Merchant $m) => $m->getId())
            ->map(fn (Merchant $m) => $this->calculateMerchantMobileRecommendation(
                $m,
                $user,
                $context
            ))
            ->filterNotNull()
            ->toArrayWithKeys();
    }

    /**
     * @return ?array{Merchant, MerchantMobileRecommendation}
     */
    private function calculateMerchantMobileRecommendation(
        Merchant $merchant,
        Usr $user,
        Context $context
    ): ?array {
        $userId = $user->getId();
        $offerResult = $this->spentAnalysis->buildCardsListToOffer(
            $merchant,
            OfferQuery::SOURCE_MOBILE_BARCODE_RECOMMENDATION,
            $user,
            [],
            null,
            [SpentAnalysisService::CARDS_GROUP_LIST],
            $context,
            false
        );
        $listCards = $offerResult[1][SpentAnalysisService::CARDS_GROUP_LIST];
        // add more eligible cards
        /** @var OfferCreditCardItem[] $eligibleCards */
        $eligibleCards = $listCards;
        $topHasUserCard = null;
        $topRecommendationCard = null;

        foreach ($eligibleCards as $eligibleCard) {
            if (!isset(
                $eligibleCard->picturePath,
                $eligibleCard->value
            )) {
                continue;
            }

            // fallback for extractPlainTextRecommendation
            $eligibleCard->earnsOnPlainText = $eligibleCard->description;
            $eligibleCard->multiplierPlainText = $eligibleCard->multiplier . 'x ';

            if (null === $topHasUserCard && $eligibleCard->isUserHas) {
                $topHasUserCard = $eligibleCard;
            }

            if (null === $topRecommendationCard) {
                $topRecommendationCard = $eligibleCard;
            }

            if ($topRecommendationCard && $topHasUserCard) {
                if ($topRecommendationCard->creditCard->getId() === $topHasUserCard->creditCard->getId()) {
                    $topRecommendationCard = null;
                }

                break;
            }
        }

        if (!array_key_exists($userId, $this->transactionsExists)) {
            $this->transactionsExists[$userId] = $this->spentAnalysis->isUserTransactionsExists($user);
        }

        return ($topRecommendationCard || $topHasUserCard) ?
            [
                $merchant,
                new MerchantMobileRecommendation(
                    $topHasUserCard,
                    $topRecommendationCard,
                    $this->transactionsExists[$userId]
                ),
            ] :
            null;
    }

    private static function extractPlainTextRecommendation(OfferCreditCardItem $offerItem): ?OfferCreditCardItem
    {
        if (!\preg_match('#<strong>(.*)</strong>#', $offerItem->description, $matches)) {
            return null;
        }

        $multiplierPlainText = $matches[1];

        if (!\preg_match('#<sub>(.*)</sub>#', $offerItem->description, $matches)) {
            return null;
        }

        $offerItem->earnsOnPlainText = $matches[1];
        $offerItem->multiplierPlainText = $multiplierPlainText;

        return $offerItem;
    }
}
