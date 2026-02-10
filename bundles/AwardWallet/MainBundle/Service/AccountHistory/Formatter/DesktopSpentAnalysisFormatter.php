<?php

namespace AwardWallet\MainBundle\Service\AccountHistory\Formatter;

use AwardWallet\MainBundle\Controller\SpentAnalysisController;
use AwardWallet\MainBundle\Globals\Localizer\LocalizeService;
use AwardWallet\MainBundle\Service\AccountHistory\SpentAnalysisQuery;

class DesktopSpentAnalysisFormatter implements SpentAnalysisFormatterInterface
{
    private LocalizeService $localizer;

    public function __construct(LocalizeService $localizer)
    {
        $this->localizer = $localizer;
    }

    public function format(array $rows, SpentAnalysisQuery $query, array $totals)
    {
        if (null !== $query->getMerchant()) {
            return [
                'ids' => $query->getSubAccountIds(),
                'merchant' => $query->getMerchant(),
                'rows' => $this->formatRows($rows),
                'totals' => $totals,
            ];
        } else {
            return [
                'ids' => $query->getSubAccountIds(),
                'startDate' => $rows['realStartDate'],
                'endDate' => $rows['realEndDate'],
                'rows' => $this->formatRows($rows['data']),
            ];
        }
    }

    private function formatRows(array $rows)
    {
        $currency = SpentAnalysisController::DEFAULT_CURRENCY; // TODO: в дальнейшем нужно растачивать, если подключаем банки других стран

        foreach ($rows as &$row) {
            $row['amountFormatted'] = $this->localizer->formatCurrency($row['amount'], $currency, false);
            $row['milesFormatted'] = $row['miles'] === null ? null : $this->localizer->formatNumber($row['miles'], 0);
            $row['potentialMilesFormatted'] = $this->localizer->formatNumber($row['potentialMiles']);

            $row['potentialMilesValueFormatted'] = $this->localizer->formatCurrency($row['potentialMilesValue'] ?? 0, $currency, false);
            $row['milesValueFormatted'] = $this->localizer->formatCurrency($row['milesValue'] ?? 0, $currency, false);

            $row['formatted'] = [
                'amount' => $this->localizer->formatCurrency($row['amount'], $currency, false),
                'miles' => $row['miles'] ? $this->localizer->formatNumber($row['miles'], 0) : null,
                'milesValue' => $this->localizer->formatCurrency($row['milesValue'] ?? 0, $currency, false),
                'minValue' => $this->localizer->formatCurrency($row['minValue'] ?? 0, $currency, false),
                'maxValue' => $this->localizer->formatCurrency($row['maxValue'] ?? 0, $currency, false),
                'potentialMiles' => $this->localizer->formatNumber($row['potentialMiles']),
                'potentialMilesValue' => $this->localizer->formatCurrency($row['potentialMilesValue'] ?? 0, $currency, false),
                'potentialMinValue' => $this->localizer->formatCurrency($row['potentialMinValue'] ?? 0, $currency, false),
                'potentialMaxValue' => $this->localizer->formatCurrency($row['potentialMaxValue'] ?? 0, $currency, false),
            ];
        }

        return $rows;
    }
}
