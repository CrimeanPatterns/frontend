<?php

namespace AwardWallet\MainBundle\Service\AccountHistory;

use AwardWallet\MainBundle\Entity\CreditCard;
use AwardWallet\MainBundle\Entity\Provider;
use AwardWallet\MainBundle\Service\MileValue\MileValueCalculator;
use AwardWallet\MainBundle\Service\MileValue\MileValueCards;
use AwardWallet\MainBundle\Service\MileValue\MileValueCost;
use AwardWallet\MainBundle\Service\MileValue\MileValueService;

class HistoryRowValueCalculator
{
    private MileValueService $mileValueService;
    private MileValueCards $mileValueCards;

    public function __construct(
        MileValueService $mileValueService,
        MileValueCards $mileValueCards
    ) {
        $this->mileValueService = $mileValueService;
        $this->mileValueCards = $mileValueCards;
    }

    public function calculate(
        CreditCard $creditCard,
        ?float $miles,
        ?float $amount,
        int $providerId,
        ?Context $context = null
    ): HistoryRowValue {
        $minValue = $maxValue = null;

        if ($creditCard->isCashBackOnly()) {
            $pointValue = $miles;
            $miles = null;

            if (0.0 === $amount) {
                $multiplier = null;
            } else {
                $milesVal = CreditCard::CASHBACK_TYPE_USD === $creditCard->getCashBackType()
                    ? $pointValue * 100
                    : $pointValue * 1;

                if (CreditCard::CASHBACK_TYPE_POINT === $creditCard->getCashBackType()) {
                    $miles = $milesVal;
                    $pointValueItem = $this->mileValueCards->getCardMileValueCost($creditCard);

                    if (Provider::CHASE_ID === $creditCard->getProvider()->getId()) {
                        $pointValue = ($miles * $pointValueItem->getPrimaryValue()) / 100;
                    } else {
                        $pointValue /= 100;
                    }
                }

                /*
                // $cost = $this->mileValueCards->getCashBackCostHundred($creditCard);
                // $milesVal = $pointValue * $cost;

                $pointValueItem = $this->mileValueCards->getCardMileValueCost($creditCard);
                $milesVal = $pointValue * $pointValueItem->getPrimaryValue();

                if ($pointValueItem->isCashBackOnly() && CreditCard::CASHBACK_TYPE_POINT === $pointValueItem->getCashBackType()) {
                    $pointValue /= 100;
                }
                */

                $multiplier = round(round($milesVal) / $amount, 1);
                $multiplier = round(round($multiplier * 2) / 2, 1); // discard rounding errors
            }
        } else {
            $multiplier = MultiplierService::calculate(
                $amount,
                $miles,
                $providerId
            );
            //            $miles = round($amount * $multiplier);
            // $pointValueItem = $this->mileValueService->getMileValueViaCreditCardId($creditCard, $context);
            $pointValueItem = $this->mileValueCards->getCardMileValueCost($creditCard);
            $pointValue = null === $pointValueItem->getPrimaryValue()
                ? null
                : MileValueCalculator::calculateEarning($pointValueItem->getPrimaryValue(), $miles);

            if (null !== $pointValueItem->getMinValue()) {
                $minValue = MileValueCalculator::calculateEarning($pointValueItem->getMinValue(), $miles);
                $maxValue = MileValueCalculator::calculateEarning($pointValueItem->getMaxValue(), $miles);
            }
        }

        return new HistoryRowValue(
            $miles,
            $pointValue,
            $multiplier,
            $minValue,
            $maxValue,
            $pointValueItem ?? new MileValueCost(null),
        );
    }
}
