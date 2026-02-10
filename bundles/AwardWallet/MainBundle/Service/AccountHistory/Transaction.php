<?php

namespace AwardWallet\MainBundle\Service\AccountHistory;

use AwardWallet\MainBundle\DependencyInjection\Annotation\NoDI;
use AwardWallet\MainBundle\Entity\AccountHistory;
use AwardWallet\MainBundle\Entity\CreditCard;
use AwardWallet\MainBundle\Entity\ShoppingCategory;
use AwardWallet\MainBundle\Service\MileValue\MileValueCards;
use AwardWallet\MainBundle\Service\MileValue\MileValueCost;

/**
 * @NoDI()
 */
class Transaction
{
    /** @var string */
    public $uuid;
    /** @var \DateTime */
    public $date;
    /** @var string */
    public $dateFormatted;
    /** @var string */
    public $description;
    /** @var string */
    public $cardName;
    /** @var string */
    public $category;
    public string $rawCategory;
    /** @var float */
    public $amount;
    /** @var string */
    public $amountFormatted;
    /** @var float */
    public $miles;
    /** @var string */
    public $milesFormatted;
    /** @var float */
    public $potential;
    /** @var float */
    public $potentialMiles;
    /** @var string */
    public $potentialMilesFormatted;
    /** @var string */
    public $potentialColor;
    /** @var string */
    public $currency;
    /** @var float */
    public $multiplier;
    /** @var float */
    public $pointsValue;
    /** @var string */
    public $pointsValueFormatted;
    /** @var float */
    public $potentialPointsValue;
    /** @var string */
    public $potentialPointsValueFormatted;
    /** @var int */
    public $subAccountId;
    public bool $isRefill = false;
    public ?bool $isSpend = null;

    public ?float $minValue;
    public ?float $maxValue;
    public ?float $potentialMinValue;
    public ?float $potentialMaxValue;
    public ?string $pointName;
    public ?float $cashEquivalent = 0;
    public ?bool $isProfit = false;
    public ?string $diffCashEq = '';

    public array $formatted = [
        'date' => '',
        'miles' => '',
        'amount' => '',
        'pointsValue' => '',
        'pointsValueCut' => '',
        'potentialPointsValue' => '',
        'minValue' => '',
        'maxValue' => '',
        'potentialMinValue' => '',
        'potentialMaxValue' => '',
        'cashEquivalent' => '',
    ];

    public function __construct(
        string $uuid,
        \DateTime $date,
        ?float $amount,
        ?float $miles,
        ?float $pointsValue,
        ?string $description,
        ?string $cardName,
        ?string $category,
        ?string $rawCategory,
        ?string $currency,
        ?int $subAccountId,
        ?float $multiplier = null,
        ?float $minValue = null,
        ?float $maxValue = null,
        ?string $pointName = ''
    ) {
        $this->date = $date;
        $this->amount = $amount;
        $this->miles = $miles;
        $this->description = $description;
        $this->cardName = $cardName;
        $this->category = $category;
        $this->rawCategory = $rawCategory;
        $this->currency = $currency;
        $this->uuid = $uuid;
        $this->pointsValue = $pointsValue;
        $this->subAccountId = $subAccountId;
        $this->multiplier = $multiplier;
        $this->minValue = $minValue;
        $this->maxValue = $maxValue;
        $this->pointName = $pointName;
    }

    public static function createFromAccountHistory(
        AccountHistory $row,
        callable $calcMileValue,
        ?Context $context = null,
        MileValueCards $mileValueCards
    ) {
        $subAccount = $row->getSubaccount();
        [$cardName, $pointValue] = [null, null];

        if ($subAccount && null !== $subAccount->getCreditcard()) {
            $cardName = $subAccount->getCreditCardFormattedDisplayName();
            //            $pointValue = $subAccount->getCreditcard() ? $subAccount->getCreditcard()->getPointValue() : 0;
            $pointValueItem = $calcMileValue($subAccount->getCreditcard()->getId(), $context);
            $pointValue = $pointValueItem instanceof MileValueCost ? $pointValueItem->getPrimaryValue() : $pointValueItem;
        }
        $currency = $row->getCurrency() ? $row->getCurrency()->getCode() : null;

        $merchantCategory = $row->getMerchant() ? $row->getMerchant()->chooseShoppingCategory() : null;
        $category = $merchantCategory ?? $row->getShoppingcategory();

        if ($category && in_array($category->getId(), ShoppingCategory::IGNORED_CATEGORIES)) {
            $category = null;
        }

        if ($category && $category->getGroup()) {
            $category = $category->getGroup();
        }

        // TODO: convert this static method to factory, and use HistoryRowValueCalculator
        if (null !== $subAccount->getCreditcard()
            && $subAccount->getCreditcard()->isCashBackOnly()
        ) {
            $miles = null;
            $pointValue = $row->getMiles();

            if ($row->getAmount() === 0) {
                $multiplier = null;
            } else {
                $milesVal = CreditCard::CASHBACK_TYPE_USD === $subAccount->getCreditcard()->getCashBackType()
                    ? $row->getMiles() * 100
                    : $row->getMiles() * 1;
                /*
                $cost = $mileValueCards->getCashBackCostHundred($subAccount->getCreditcard());
                $milesVal = $row->getMiles() * $cost;
                */

                $multiplier = round(round($milesVal) / $row->getAmount(), 1);
                $multiplier = round(round($multiplier * 2) / 2, 1); // discard rounding errors
            }
        } elseif ($row->getAmount() === null || empty($row->getMiles())) {
            $multiplier = null;
            $miles = null;
            $pointValue = null;
        } else {
            $multiplier = MultiplierService::calculate(
                $row->getAmount(),
                $row->getMiles(),
                $row->getAccount()->getProviderid()->getId()
            );
            $miles = round($row->getAmount() * $multiplier);
            $pointValue = round($pointValue * 0.01 * $miles, 2);
        }

        $result = new self(
            $row->getUuid(),
            $row->getPostingdate(),
            $row->getAmount(),
            $miles,
            $pointValue,
            $row->getDescription(),
            $cardName,
            htmlspecialchars_decode((string) $category),
            htmlspecialchars_decode($row->getCategory() ?? ''),
            $currency,
            $subAccount->getSubaccountid()
        );

        $result->multiplier = $multiplier;

        if (null !== $subAccount->getCreditcard()) {
            $result->pointName = $subAccount->getCreditcard()->getPointName();
        }

        return $result;
    }
}
