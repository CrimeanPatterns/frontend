<?php

namespace AwardWallet\MainBundle\Service\AccountHistory\Formatter;

use AwardWallet\MainBundle\Controller\SpentAnalysisController as DesktopSpentAnalysisController;
use AwardWallet\MainBundle\Entity\Provider;
use AwardWallet\MainBundle\Entity\Repositories\ProviderRepository;
use AwardWallet\MainBundle\Globals\ApiVersioning\ApiVersioningService;
use AwardWallet\MainBundle\Globals\ApiVersioning\MobileVersions;
use AwardWallet\MainBundle\Globals\Features\FeaturesMap;
use AwardWallet\MainBundle\Globals\Localizer\LocalizeService;
use AwardWallet\MainBundle\Service\AccountHistory\Formatter\MobileHistoryFormatter\BlockFactory;
use AwardWallet\MainBundle\Service\AccountHistory\Formatter\MobileHistoryFormatter\Blocks\BarChart;
use AwardWallet\MainBundle\Service\AccountHistory\Formatter\MobileHistoryFormatter\Blocks\Block;
use AwardWallet\MainBundle\Service\AccountHistory\Formatter\MobileHistoryFormatter\Blocks\EarningPotential;
use AwardWallet\MainBundle\Service\AccountHistory\Formatter\MobileHistoryFormatter\Blocks\Row;
use AwardWallet\MainBundle\Service\AccountHistory\Formatter\MobileHistoryFormatter\RowFormat2;
use AwardWallet\MainBundle\Service\AccountHistory\HistoryService;
use AwardWallet\MainBundle\Service\AccountHistory\SpentAnalysisQuery;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

use function AwardWallet\MainBundle\Globals\Utils\iterFluent\it;

class MobileSpentAnalysisFormatter implements SpentAnalysisFormatterInterface
{
    protected const CHARTS_MAX_COUNT = 4;
    protected const POINTS_VALUE_RANGE = 'points_value_range';

    private LocalizeService $localizer;
    private BlockFactory $blockFactory;
    private TranslatorInterface $translator;
    private ProviderRepository $providerRepository;
    private AuthorizationCheckerInterface $authorizationChecker;
    private ApiVersioningService $apiVersioning;

    private bool $isSpentFormatV2 = false;

    public function __construct(
        LocalizeService $localizeService,
        BlockFactory $blockFactory,
        TranslatorInterface $translator,
        ProviderRepository $providerRepository,
        AuthorizationCheckerInterface $authorizationChecker,
        ApiVersioningService $apiVersioning
    ) {
        $this->localizer = $localizeService;
        $this->blockFactory = $blockFactory;
        $this->translator = $translator;
        $this->providerRepository = $providerRepository;
        $this->authorizationChecker = $authorizationChecker;
        $this->apiVersioning = $apiVersioning;
    }

    public function format(array $data, SpentAnalysisQuery $query, array $totals = [])
    {
        $features = $this->createFeatures();
        $this->isSpentFormatV2 = $this->apiVersioning->supports(MobileVersions::SPENT_ANALYSIS_FORMAT_V2);

        if (null !== $query->getMerchant()) {
            $result = [
                'ids' => $query->getSubAccountIds(),
                'merchant' => $query->getMerchant(),
                'rows' => $this->formatRows($data, true, $features),
                'totals' => $this->formatTotals($totals),
            ];
        } else {
            $result = [
                'offerFilterIds' => $query->getOfferFilterIds(),
                'rows' => $this->formatRows($data['data'], false, $features),
                'charts' => $this->formatCharts($data['data']),
            ];

            if (isset($data['realEndDate'], $data['realStartDate'])) {
                [$formData, $spentData] = $query->getExtraData();
                $dateRange =
                    it($spentData['dateRanges'] ?? [])
                    ->filterByColumn('value', $formData['date_range'])
                    ->column('name')
                    ->first();
                $owner = $formData['owner'];

                if (isset($dateRange, $spentData['ownersList'][$owner]['name'])) {
                    $result['title'] = $this->translator->trans('spent-analysis.merchant.spend-analysis-for', [
                        '%date_range%' => $dateRange . ' ',
                        '%owner_name%' => $spentData['ownersList'][$owner]['name'],
                    ]);
                }

                $result['subTitle'] = $this->translator->trans('spent-analysis.actual-transactions.analyzed-between', [
                    '%date_start%' => $this->localizer->formatDate(new \DateTime($data['realStartDate']), 'long'),
                    '%date_end%' => $this->localizer->formatDate(new \DateTime($data['realEndDate']), 'long'),
                ]);
                $result['avatar'] = $spentData['ownersList'][$owner]['avatar'] ?? null;
            } else {
                $result['notice'] = $this->translator->trans('spent-analysis.transactions.not-detected', [
                    '%accounts_last_updated%' => '',
                    '%link_update_on%' => '',
                    '%link_update_off%' => '',
                ]);
            }
        }

        return $result;
    }

    public function getEligibleProvidersList(): array
    {
        $providerIdsMap = \array_flip(Provider::EARNING_POTENTIAL_LIST);
        $providers = $this->providerRepository->findBy(['providerid' => Provider::EARNING_POTENTIAL_LIST]);

        return
            it($providers)
            ->usort(function (Provider $provider1, Provider $provider2) use ($providerIdsMap) {
                return $providerIdsMap[$provider1->getProviderid()] <=> $providerIdsMap[$provider2->getProviderid()];
            })
            ->map(function (Provider $provider) {
                return [
                    'id' => $provider->getId(),
                    'name' => $provider->getDisplayname(),
                    'code' => $provider->getCode(),
                ];
            })
            ->toArray();
    }

    protected function formatRows(array $rows, bool $withDate, FeaturesMap $features)
    {
        if ($withDate) {
            $result = it($rows)
                ->map(function (array $dataRow) {
                    $date = new \DateTime($dataRow['postingDate']);
                    $dataRow['date'] = $date;
                    $dataRow['dateYM'] = $date->format('Y-m');

                    return $dataRow;
                })
                ->groupAdjacentByColumn('dateYM')
                ->flatMap(function (array $monthRows) use ($features) {
                    $firstRowInMonth = $monthRows[0];

                    yield $this->blockFactory->createDateBlock($firstRowInMonth['date']);

                    foreach ($monthRows as $monthRow) {
                        yield $this->formatRow($monthRow, $features);
                    }
                })
                ->toArray();
        } else {
            $result = [];

            foreach ($rows as $row) {
                $result[] = $this->formatRow($row, $features);
            }
        }

        return $result;
    }

    /**
     * @param BarChart[] $rows
     */
    protected function formatCharts(array $rows): array
    {
        $rows = \array_slice($rows, 0, -1); // exclude Total

        if (\count($rows) > 1) {
            $other =
                it($rows)
                ->reverseList()
                ->take(1)
                ->filterByColumn('merchantName', 'Other')
                ->toArray();
            $rows =
                it($rows)
                ->take(self::CHARTS_MAX_COUNT - \count($other))
                ->chain($other);
        } else {
            $rows = it($rows);
        }

        return
            $rows
            ->map(function (array $row) { return $this->formatGraph($row); })
            ->toArray();
    }

    protected function formatGraph(array $row): BarChart
    {
        $result = new BarChart();
        $result->name = $row['merchantName'];
        $result->value = (float) $row['miles'];
        $result->pointsValue = $this->localizer->formatCurrency(
            $row['milesValue'] ?? 0,
            DesktopSpentAnalysisController::DEFAULT_CURRENCY
        );
        $result->potentialValue = (float) $row['potentialMiles'];
        $result->potentialPointsValue = $this->localizer->formatCurrency(
            $row['potentialMilesValue'] ?? 0,
            DesktopSpentAnalysisController::DEFAULT_CURRENCY
        );
        $result->amount = $this->localizer->formatCurrency(
            (float) $row['amount'],
            DesktopSpentAnalysisController::DEFAULT_CURRENCY
        );

        return $result;
    }

    /**
     * @return Row|RowFormat2
     */
    protected function formatRow(array $dataRow, FeaturesMap $features)
    {
        $result = $this->isSpentFormatV2 ? new RowFormat2() : new Row();

        if (isset($dataRow['miles'])) {
            $dataRow['miles'] = \round($dataRow['miles']);
        }

        if (isset($dataRow['date'])) {
            $result->date = $this->isSpentFormatV2
                ? $this->localizer->formatDate($dataRow['date'], LocalizeService::FORMAT_MEDIUM)
                : $this->blockFactory->createDatePart($dataRow['date']);
        } else {
            $result->merchant = $dataRow['merchantId'] ?? null;
        }

        if ($this->isSpentFormatV2) {
            $isMerchant = array_key_exists('transactions', $dataRow);

            $result->style = $dataRow['miles'] >= 0 ? 'positive' : 'negative';
            $result->title = $dataRow['creditCardName'] ?? $dataRow['merchantName'];
            $result->category = $dataRow['category'] ?? $dataRow['merchantCategory'] ?? null;
            $result->value = $this->localizer->formatCurrency(
                (float) $dataRow['amount'],
                DesktopSpentAnalysisController::DEFAULT_CURRENCY
            );

            if ($isMerchant) {
                $result->totalTransactions = $this->localizer->formatNumber($dataRow['transactions'] ?? 0);
                unset($result->creditCard);
            } else {
                $result->title = $result->date;
                $result->creditCard = $dataRow['creditCardName'];
            }

            $pointName = $dataRow['pointNames'] ?? null;

            if (!$pointName && !empty($dataRow['pointName'])) {
                $pointName = [$dataRow['pointName']];
            }

            $result->earned = [
                'multiplier' => ($dataRow['multiplier'] > 0 ? $dataRow['multiplier'] . 'x' : ''),
                'value' => empty($dataRow['miles'])
                    ? 0
                    : (
                        ($dataRow['miles'] > 0 ? '+' : '-')
                        . $this->localizer->formatNumber($dataRow['miles'])
                    ),
                'pointsValue' => $dataRow['milesValue'] ?? 0,
                'pointName' => $pointName,
            ];
            $result->cashEquivalent = [
                'value' => empty($dataRow['milesValue'])
                    ? 0
                    : $this->localizer->formatCurrencyShort(
                        $dataRow['milesValue'],
                        DesktopSpentAnalysisController::DEFAULT_CURRENCY,
                        ['th' => 1000, 'h' => 10]
                    ),
                'diffCashEq' => $dataRow['diffCashEq'] ?? null,
                'isProfit' => $dataRow['isProfit'] ?? false,
            ];
            $result->extraData = [
                'amount' => $dataRow['amount'],
                'miles' => $dataRow['miles'],
                'cashEquivalent' => $dataRow['milesValue'] ?? 0,
                'cashEquivalentShort' => $this->localizer->formatCurrencyShort(
                    $dataRow['milesValue'] ?? 0,
                    null,
                    ['th' => 1000, 'h' => 10]
                ),
            ];
            $result->uuid = $dataRow['UUID'] ?? null;
        } else {
            $result->blocks =
                it(
                    $this->blockFactory->createTitleBlock(
                        $this->cellByValue($dataRow['creditCardName'] ?? $dataRow['merchantName']),
                        $this->cellByValue($dataRow['amount']),
                        DesktopSpentAnalysisController::DEFAULT_CURRENCY
                    )
                )
                    ->chain(
                        $this->blockFactory->createStringBlock($this->cell(
                            '# of transactions',
                            isset($dataRow['transactions']) ? $this->localizer->formatNumber($dataRow['transactions']) : null
                        ))
                    )
                    ->chain(
                        $this->blockFactory->createStringBlock($this->cell(
                            'Category',
                            $dataRow['category'] ?? null
                        ))
                    )
                    ->chain(
                        it($this->blockFactory->createBalanceBlock(
                            \array_merge(
                                $this->cell('Points', $dataRow['miles']),
                                [
                                    'multiplier' => $dataRow['multiplier'],
                                    'pointsValue' => $dataRow['milesValue'] ?? 0,
                                    'currency' => DesktopSpentAnalysisController::DEFAULT_CURRENCY,
                                ],
                                (
                                    isset($dataRow['maxValue'], $dataRow['minValue'])
                                    && (\abs($dataRow['maxValue'] - $dataRow['minValue']) > 0.001)
                                ) ?
                                    ['pointsValueRange' => [$dataRow['minValue'], $dataRow['maxValue']]] :
                                    []
                            ),
                            ''
                        ))
                            ->onEach(function (Block $block) use ($features) {
                                $block->pointsValue = self::formatRange($block, $features);
                                $block->pointsValueRange = null;
                            })
                    )
                    ->chain($this->createEarningPotentialBlock($dataRow, $features))
                    ->toArray();
        }

        return $result;
    }

    protected function formatTotals(array $totals): array
    {
        if (empty($totals)) {
            return [];
        }

        $totals['multiplier'] .= ($totals['multiplier'] > 0 ? 'x' : '');
        $totals['extraData'] = [
            'amount' => $totals['amount'],
            'miles' => $totals['miles'],
            'cashEquivalentRaw' => $totals['milesValue'],
            'cashEquivalent' => $this->localizer->formatCurrency(
                $totals['milesValue'],
                DesktopSpentAnalysisController::DEFAULT_CURRENCY
            ),
            'cashEquivalentShort' => $this->localizer->formatCurrencyShort(
                $totals['milesValue'],
                null,
                ['th' => 1000, 'h' => 10]
            ),
        ];

        foreach (['miles', 'milesValue'] as $numberField) {
            $totals[$numberField] = $this->localizer->formatNumber($totals[$numberField]);
        }

        foreach (['amount', 'average', 'potentialMilesValue'] as $currencyField) {
            $totals[$currencyField] = $this->localizer->formatCurrency(
                $totals[$currencyField],
                DesktopSpentAnalysisController::DEFAULT_CURRENCY
            );
        }

        return $totals;
    }

    protected function createEarningPotentialBlock(array $row, FeaturesMap $features): iterable
    {
        if ($row['miles'] <= 0) {
            return;
        }

        $cell = $this->cell(
            'Earning Potential',
            $row['potentialMiles']
        );

        if (array_key_exists('isProfit', $row) && $row['isProfit']) {
            $cell['type'] = HistoryService::FIELD_TYPE_THUMB_UP;
        } else {
            $cell['type'] = HistoryService::FIELD_TYPE_OFFER;
            $cell['color'] = $row['earningPotentialColor'] ?? null;

            $cell['multiplier'] = $row['potential'] ?? 0;
            $cell['pointsValue'] = $row['potentialMilesValue'] ?? 0;
            $cell['currency'] = DesktopSpentAnalysisController::DEFAULT_CURRENCY;
        }

        if (
            isset($row['potentialMaxValue'], $row['potentialMinValue'])
            && (\abs($row['potentialMaxValue'] - $row['potentialMinValue']) >= 0.001)
        ) {
            $cell['pointsValueRange'] = [
                $row['potentialMinValue'],
                $row['potentialMaxValue'],
            ];
        }

        yield from it($this->blockFactory->createEarningPotentialBlock($cell, $row['UUID'] ?? null, ''))
            ->onEach(function (EarningPotential $earningPotential) use ($row, $features) {
                if (isset($row['amount'], $row['miles'])) {
                    $earningPotential->extraData = [
                        'amount' => $row['amount'],
                        'miles' => $row['miles'],
                    ];
                }

                $earningPotential->pointsValue = self::formatRange($earningPotential, $features);
                $earningPotential->pointsValueRange = null;
            });
    }

    protected function cellByName(?string $name): array
    {
        return $this->cell($name, null);
    }

    protected function cellByValue(?string $value): array
    {
        return $this->cell(null, $value);
    }

    protected function cell(?string $name, ?string $value): array
    {
        return ['column' => $name, 'value' => $value];
    }

    protected function createFeatures(): FeaturesMap
    {
        return new FeaturesMap([
            self::POINTS_VALUE_RANGE => true,
        ]);
    }

    private static function formatRange(Block $block, FeaturesMap $features): ?string
    {
        return $features->supports(self::POINTS_VALUE_RANGE) ?
            self::doFormatRange($block) :
            $block->pointsValue;
    }

    private static function doFormatRange(Block $block): ?string
    {
        return $block->pointsValueRange ?
            "{$block->pointsValueRange[0]} - {$block->pointsValueRange[1]}" :
            $block->pointsValue;
    }
}
