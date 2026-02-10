<?php

namespace AwardWallet\MainBundle\Service\AccountHistory;

use AwardWallet\MainBundle\DependencyInjection\Annotation\NoDI;

/**
 * @NoDI()
 */
class TransactionTotals
{
    /** @var float */
    public $amount;
    /** @var float */
    public $average;
    /** @var float */
    public $miles;
    /** @var float */
    public $potentialMiles;
    /** @var int */
    public $transactions;
    /** @var string */
    public $amountFormatted;
    /** @var string */
    public $averageFormatted;
    /** @var string */
    public $milesFormatted;
    /** @var string */
    public $potentialMilesFormatted;
    /** @var string */
    public $potentialColor;
    /** @var string */
    public $transactionsFormatted;
    /** @var string */
    public $currency;
    /** @var float */
    public $multiplier;
    /** @var float */
    public $potential;
    /** @var array */
    public $categories;
    public float $pointsValue;
    public float $potentialPoints;
    public string $pointsValueFormatted;
    public string $potentialPointsFormatted;
    public float $cashEquivalent;

    public function __construct(
        ?float $amount,
        ?float $miles,
        ?float $potentialMiles,
        ?int $transactions,
        ?string $currency = null,
        ?array $categories = [],
        ?float $pointsValue = 0,
        ?float $potentialPoints = 0,
        ?float $cashEquivalent = 0
    ) {
        $this->amount = round($amount, 2);
        $this->miles = round($miles, 2);
        $this->potentialMiles = round($potentialMiles, 2);
        $this->transactions = $transactions;
        $this->average = $transactions > 0 ? round($amount / $transactions, 2) : 0;
        $this->currency = $currency;
        $this->categories = $categories;
        $this->pointsValue = $pointsValue;
        $this->potentialPoints = $potentialPoints;
        $this->cashEquivalent = $cashEquivalent;

        if ($amount && $miles && $potentialMiles) {
            $this->multiplier = $miles > 0 ? MultiplierService::calculate($amount, $miles, 0) : 0;
            $this->potential = $potentialMiles > 0 ? MultiplierService::calculate($amount, $potentialMiles, 0) : 0;
        }

        if ($this->multiplier && $this->potential) {
            $this->potentialColor = BankTransactionsAnalyser::potentialDiffColor($this->potential, $this->multiplier);
        }
    }
}
