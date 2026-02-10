<?php

namespace AwardWallet\MainBundle\Service\AccountHistory\Formatter;

use AwardWallet\MainBundle\Globals\Localizer\LocalizeService;
use AwardWallet\MainBundle\Service\AccountHistory\HistoryQuery;
use AwardWallet\MainBundle\Service\AccountHistory\HistoryService;

class DesktopHistoryFormatter implements HistoryFormatterInterface
{
    private LocalizeService $localizer;

    public function __construct(LocalizeService $localizer)
    {
        $this->localizer = $localizer;
    }

    public function format(array $rows, HistoryQuery $historyQuery): ?array
    {
        return array_map(function (array $row) use ($historyQuery) {
            return $this->formatRow($row, $historyQuery);
        }, $rows);
    }

    public function formatRow(array $row, HistoryQuery $historyQuery): ?array
    {
        $row['pointsValue'] = $this->localizer->formatCurrency($row['pointsValue'], 'USD');
        $row['potentialPointsValue'] = $this->localizer->formatCurrency($row['potentialPointsValue'], 'USD');

        foreach ($row['cells'] as &$cell) {
            $value = $cell['value'];

            if (empty(trim($value))) {
                $cell['valueFormatted'] = trim($value) !== '0' ? '' : $value;

                continue;
            }

            switch ($cell['field']) {
                case 'PostingDate':
                    $value = $this->localizer->formatDateTime(new \DateTime($value), 'short', 'none');

                    break;

                case 'Description':
                case 'Note':
                    $value = html_entity_decode($value);

                    break;

                case 'Amount':
                case 'AmountBalance':
                    if ((float) $value !== 0.00) {
                        if (empty($row['currency'])) {
                            $value = (float) $value;
                        } else {
                            $value = $this->localizer->formatCurrency((float) $value, $row['currency']);
                        }
                    } else {
                        $value = '';
                    }

                    break;

                case 'MilesBalance':
                case 'Miles':
                case HistoryService::EARNING_POTENTIAL_COLUMN:
                    if ($value !== null && $value !== '') {
                        $sign = (float) $value > 0 ? "+" : "";
                        $value = $sign . $this->localizer->formatNumber((float) $value);
                    }

                    break;

                case 'Category':
                    $value = html_entity_decode($value);

                    break;

                case 'Bonus':
                    if ($value !== null && $value !== '') {
                        $value = $this->localizer->formatNumber(
                            filterBalance($value, false)
                        );
                    }

                    break;

                case 'Info':
                    switch ($cell['valueType']) {
                        case 'decimal':
                            $value = $this->localizer->formatNumber(
                                filterBalance($value, true)
                            );

                            break;

                        case 'integer':
                            $value = $this->localizer->formatNumber(
                                filterBalance($value, false)
                            );

                            break;

                        case 'date':
                            try {
                                if ((int) $value > 946684800) { // date > 2000/01/01
                                    $value = (new \DateTime())->setTimestamp($value);
                                } else {
                                    $value = new \DateTime($value);
                                }
                                $value = $this->localizer->formatDateTime($value, 'short', 'none');
                            } catch (\Exception $e) {
                                $value = '';

                                break;
                            }

                            break;

                        default:
                            $value = html_entity_decode($value);
                    }
            }
            $cell['valueFormatted'] = $value;
        }

        return $row;
    }

    public function getId(): string
    {
        return HistoryFormatterInterface::DESKTOP;
    }
}
