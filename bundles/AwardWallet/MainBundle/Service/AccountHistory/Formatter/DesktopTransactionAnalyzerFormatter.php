<?php

namespace AwardWallet\MainBundle\Service\AccountHistory\Formatter;

use AwardWallet\MainBundle\Controller\SpentAnalysisController;
use AwardWallet\MainBundle\Globals\Localizer\LocalizeService;
use AwardWallet\MainBundle\Service\AccountHistory\Transaction;
use AwardWallet\MainBundle\Service\AccountHistory\TransactionTotals;

class DesktopTransactionAnalyzerFormatter
{
    private LocalizeService $localizer;

    public function __construct(LocalizeService $localizer)
    {
        $this->localizer = $localizer;
    }

    public function formatTransactions(array $rows): ?array
    {
        return array_map([$this, 'formatRow'], $rows);
    }

    public function formatTotals(TransactionTotals $row): TransactionTotals
    {
        $row->transactionsFormatted = $this->localizer->formatNumber($row->transactions);
        $row->amountFormatted = $this->localizer->formatCurrency($row->amount, $row->currency ?? 'USD');
        $row->averageFormatted = $this->localizer->formatCurrency($row->average, $row->currency ?? 'USD');
        $row->milesFormatted = $this->localizer->formatNumber($row->miles, 0);
        $row->potentialMilesFormatted = $this->localizer->formatNumber($row->potentialMiles, 0);
        $row->pointsValueFormatted = $this->localizer->formatCurrency($row->pointsValue, $row->currency ?? 'USD');
        $row->potentialPointsFormatted = $this->localizer->formatNumber($row->potentialPoints, 0);
        $row->cashEquivalentFormatted = $this->localizer->formatCurrency($row->cashEquivalent, $row->currency ?? 'USD');

        return $row;
    }

    private function formatRow(Transaction $row): Transaction
    {
        $currency = SpentAnalysisController::DEFAULT_CURRENCY;

        $row->formatted['date'] = $this->localizer->formatDateTime($row->date, 'short', 'none');
        $row->date = null;
        $row->category = htmlspecialchars_decode($row->category ?? '');

        if ($row->miles !== null) {
            $sign = $row->miles > 0 ? "+" : "";
            $row->milesFormatted = $sign . $this->localizer->formatNumber($row->miles, 0);
            $row->formatted['miles'] = $sign . $this->localizer->formatNumber($row->miles, 0);
        }

        if ($row->potentialMiles) {
            $row->potentialMilesFormatted = "+" . $this->localizer->formatNumber($row->potentialMiles);
        }

        if ($row->potentialPointsValue) {
            $row->potentialPointsValueFormatted = $this->localizer->formatCurrency($row->potentialPointsValue, $currency);
            $row->formatted['potentialPointsValue'] = $this->localizer->formatCurrency($row->potentialPointsValue, $currency);
            $row->formatted['potentialMinValue'] = $this->localizer->formatCurrency($row->potentialMinValue, $currency);
            $row->formatted['potentialMaxValue'] = $this->localizer->formatCurrency($row->potentialMaxValue, $currency);
        }

        $row->formatted['amount'] = $this->localizer->formatCurrency($row->amount, $row->currency ?? $currency);
        $row->formatted['pointsValue'] = $this->localizer->formatCurrency($row->pointsValue, $currency);
        $row->formatted['pointsValueCut'] = $this->localizer->formatCurrency(
            $row->pointsValue > 1 ? floor($row->pointsValue) : ceil(round($row->pointsValue * 10)) / 10,
            $currency
        );
        $row->formatted['minValue'] = $this->localizer->formatCurrency(\abs($row->minValue ?? 0), $currency, false);
        $row->formatted['maxValue'] = $this->localizer->formatCurrency(\abs($row->maxValue ?? 0), $currency, false);

        return $row;
    }
}
