<?php

namespace AwardWallet\MainBundle\Service\AccountHistory\Formatter;

use AwardWallet\MainBundle\Globals\ApiVersioning\ApiVersioningService;
use AwardWallet\MainBundle\Globals\ApiVersioning\MobileVersions;
use AwardWallet\MainBundle\Globals\Features\FeaturesMap;
use AwardWallet\MainBundle\Globals\Localizer\LocalizeService;
use AwardWallet\MainBundle\Service\AccountHistory\Formatter\MobileHistoryFormatter\BlockFactory;
use AwardWallet\MainBundle\Service\AccountHistory\Formatter\MobileHistoryFormatter\Blocks\Block;
use AwardWallet\MainBundle\Service\AccountHistory\Formatter\MobileHistoryFormatter\Blocks\EarningPotential;
use AwardWallet\MainBundle\Service\AccountHistory\Formatter\MobileHistoryFormatter\Blocks\Row;
use AwardWallet\MainBundle\Service\AccountHistory\HistoryService;
use AwardWallet\MainBundle\Service\AccountHistory\Transaction;
use AwardWallet\MainBundle\Service\AccountHistory\TransactionTotals;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;

use function AwardWallet\MainBundle\Globals\Utils\iterFluent\it;

class MobileTransactionAnalyzerFormatter
{
    private const DATES_BY_DAY = 'dates_by_day';
    private const POINTS_VALUE_RANGE = 'points_value_range';

    private LocalizeService $localizer;
    private BlockFactory $blockFactory;
    private ApiVersioningService $apiVersioning;
    private AuthorizationCheckerInterface $authorizationChecker;

    public function __construct(
        LocalizeService $localizer,
        BlockFactory $blockFactory,
        ApiVersioningService $apiVersioning,
        AuthorizationCheckerInterface $authorizationChecker
    ) {
        $this->localizer = $localizer;
        $this->blockFactory = $blockFactory;
        $this->apiVersioning = $apiVersioning;
        $this->authorizationChecker = $authorizationChecker;
    }

    /**
     * @param Transaction[] $rows
     */
    public function formatTransactions(array $rows): array
    {
        $features = $this->buildFeatures();

        return
            it($rows)
            ->reindex(fn (Transaction $transaction) => $transaction->date->format($features->supports(self::DATES_BY_DAY) ? 'Y-m-d' : 'Y-m'))
            ->groupAdjacentByKey()
            ->flatMap(function (/** @var $monthRows Transaction[] */ array $rangeRows) use ($features) {
                if ($features->supports(self::DATES_BY_DAY)) {
                    yield [
                        'title' => $this->localizer->formatDate($rangeRows[0]->date, 'long'),
                        'data' =>
                            it($rangeRows)
                            ->map(fn (Transaction $transaction) => $this->formatRowV2($transaction, $features))
                            ->toArray(),
                    ];
                } else {
                    yield $this->blockFactory->createDateBlock($rangeRows[0]->date);

                    yield from it($rangeRows)
                        ->map(fn (Transaction $transaction) => $this->formatRow($transaction));
                }
            })
            ->toArray();
    }

    /**
     * @return Block[]
     */
    public function formatTotals(TransactionTotals $totals): ?array
    {
        if (!$totals->transactions) {
            return null;
        }

        // Title block
        $result = [
            'amount' => $this->localizer->formatCurrency($totals->amount, $totals->currency ?? BlockFactory::DEFAULT_CURRENCY),
            'transactions' => $this->localizer->formatNumber($totals->transactions),
            'average' => $this->localizer->formatCurrency($totals->average, $totals->currency ?? BlockFactory::DEFAULT_CURRENCY),
        ];
        // Balance block
        $result['balanceValue'] = $this->blockFactory->formatWithSign($totals->miles, null, '', 0);

        if (($totals->multiplier ?? 0) >= 1) {
            $result['balanceMultiplier'] = $this->localizer->formatNumber((float) $totals->multiplier) . 'x';
        }

        // Earning potential block
        foreach ($this->createTitleEarningPotentialBlock($totals) as $totalsBlock) {
            $result['extraData'] = $totalsBlock->extraData;
            $result['potentialColor'] = $totalsBlock->color;
            $result['type'] = $totalsBlock->type;
            $result['potentialMultiplier'] = $totalsBlock->multiplier;
            $result['potentialValue'] = $totalsBlock->value;
            $result['potentialPointsValue'] = $totalsBlock->pointsValue;

            break;
        }

        return $result;
    }

    protected function buildFeatures(): FeaturesMap
    {
        return new FeaturesMap([
            self::DATES_BY_DAY => $this->apiVersioning->supports(MobileVersions::TRANSACTION_ANALYZER_DATES_BY_DAY),
            self::POINTS_VALUE_RANGE => true,
        ]);
    }

    protected function createEarningPotentialBlock(Transaction $row): iterable
    {
        if ($row->miles <= 0) {
            return;
        }

        $cell = $this->cell(
            'Earning Potential',
            $row->potentialMiles ?? null
        );

        if ($row->potentialPointsValue > $row->pointsValue) {
            $cell['type'] = HistoryService::FIELD_TYPE_OFFER;
            $cell['color'] = $row->potentialColor ?? '';
            $cell['multiplier'] = $row->potential;
            $cell['pointsValue'] = $row->potentialPointsValue;
            $cell['currency'] = BlockFactory::DEFAULT_CURRENCY;
        } else {
            $cell['type'] = HistoryService::FIELD_TYPE_THUMB_UP;
        }

        if (
            isset($row->potentialMaxValue, $row->potentialMinValue)
            && (\abs($row->potentialMaxValue - $row->potentialMinValue) >= 0.001)
        ) {
            $cell['pointsValueRange'] = [
                $row->potentialMinValue,
                $row->potentialMaxValue,
            ];
        }

        yield from it($this->blockFactory->createEarningPotentialBlock($cell, $row->uuid ?? null))
            ->onEach(function (EarningPotential $earningPotential) use ($row) {
                if (isset($row->amount, $row->miles)) {
                    $earningPotential->extraData = [
                        'amount' => $row->amount,
                        'miles' => $row->miles,
                    ];
                }
            });
    }

    protected function createCashEquivalentBlock(Transaction $row): iterable
    {
        if ($row->miles <= 0) {
            return;
        }

        $cell = $this->cell(
            'Cash Equivalent',
            $row->cashEquivalent ?? null
        );

        $cell['isProfit'] = $row->isProfit;
        $cell['pointName'] = $row->pointName;
        $cell['diffCashEq'] = $row->diffCashEq;
        $cell['currency'] = BlockFactory::DEFAULT_CURRENCY;

        yield from it($this->blockFactory->createCashEquivalentBlock($cell, $row->uuid ?? null));
    }

    protected function createTitleEarningPotentialBlock(TransactionTotals $row): iterable
    {
        $cell = $this->cell(
            'Earning Potential',
            $row->potentialMiles ?: null
        );

        if ($row->potentialMiles) {
            $cell['type'] = HistoryService::FIELD_TYPE_OFFER;
            $cell['color'] = $row->potentialColor ?? '';
            $cell['multiplier'] = $row->potential;
        } else {
            $cell['type'] = HistoryService::FIELD_TYPE_THUMB_UP;
        }

        yield from it($this->blockFactory->createEarningPotentialBlock($cell, $row->uuid ?? null, '', 0))
            ->onEach(function (EarningPotential $earningPotential) use ($row) {
                if (isset($row->amount, $row->miles)) {
                    $earningPotential->extraData = [
                        'amount' => $row->amount,
                        'miles' => $row->miles,
                        'cashEquivalent' => $this->localizer->formatCurrency($row->cashEquivalent, $row->currency ?? BlockFactory::DEFAULT_CURRENCY),
                        'cashEquivalentRaw' => $row->cashEquivalent,
                    ];
                }
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

    protected function sign(float $value, string $formatted): string
    {
        return ($value > 0 ? '+' : '') . $formatted;
    }

    private function formatRowV2(Transaction $transaction, FeaturesMap $features): array
    {
        $transaction->miles = \round($transaction->miles);

        $result = [
            'style' => $transaction->miles >= 0 ? 'positive' : 'negative',
            'merchant' => $transaction->description,
            'creditCard' => isset($transaction->cardName) ? \htmlspecialchars_decode($transaction->cardName) : '',
            'category' => isset($transaction->category) ? \htmlspecialchars_decode($transaction->category) : '',
        ];

        $titleBlockIter = $this->blockFactory->createTitleBlock(
            $this->cellByValue($transaction->description),
            $this->cellByValue($transaction->amount),
            $transaction->currency ?? BlockFactory::DEFAULT_CURRENCY
        );

        /** @var Block $titleBlock */
        foreach ($titleBlockIter as $titleBlock) {
            $result['title'] = $titleBlock->name;
            $result['value'] = $titleBlock->value;
        }

        $balanceBlockIter = $this->blockFactory->createBalanceBlock(
            \array_merge(
                $this->cell(
                    'Points',
                    $transaction->miles
                ),
                [
                    'multiplier' => $transaction->multiplier,
                    'currency' => $transaction->currency ?? BlockFactory::DEFAULT_CURRENCY,
                    'pointsValue' => $transaction->pointsValue,
                ],
                (
                    isset($transaction->maxValue, $transaction->minValue)
                    && (\abs($transaction->maxValue - $transaction->minValue) >= 0.001)
                ) ?
                    ['pointsValueRange' => [\abs($transaction->minValue), \abs($transaction->maxValue)]] :
                    []
            )
        );

        /** @var Block $balanceBlock */
        foreach ($balanceBlockIter as $balanceBlock) {
            $result['earned'] = [
                'multiplier' => $balanceBlock->multiplier,
                'value' => $balanceBlock->value,
                'pointsValue' => self::formatRange($balanceBlock, $features),
                'pointName' => $transaction->pointName,
            ];
        }

        if (!$this->apiVersioning->supports(MobileVersions::TRANSACTION_ANALYZER_CASH_EQUIVALENT)) {
            $earnedPotentialBlockIter = $this->createEarningPotentialBlock($transaction);

            /** @var EarningPotential $earnedPotentialBlock */
            foreach ($earnedPotentialBlockIter as $earnedPotentialBlock) {
                $result['potential'] = [
                    'color' => $earnedPotentialBlock->color,
                    'multiplier' => $earnedPotentialBlock->multiplier,
                    'type' => $earnedPotentialBlock->type,
                    'value' => $earnedPotentialBlock->value,
                    'pointsValue' => self::formatRange($earnedPotentialBlock, $features),
                ];
                $result['uuid'] = $earnedPotentialBlock->uuid;
                $result['extraData'] = $earnedPotentialBlock->extraData;
            }
        } else {
            $result['cashEquivalent'] = [
                'value' => $this->localizer->formatCurrency($transaction->cashEquivalent, BlockFactory::DEFAULT_CURRENCY),
                'diffCashEq' => $transaction->diffCashEq,
                'isProfit' => $transaction->isProfit,
            ];
            $result['uuid'] = $transaction->uuid;
            $result['extraData'] = [
                'amount' => $transaction->amount,
                'miles' => $transaction->miles,
                'cashEquivalent' => $transaction->cashEquivalent,
            ];
        }

        return $result;
    }

    private function formatRow(Transaction $transaction): Row
    {
        $result = new Row();
        $result->style = $transaction->miles >= 0 ? 'positive' : 'negative';
        $result->date = $this->blockFactory->createDatePart($transaction->date);
        $result->merchant = $transaction->description;

        $result->blocks =
            it(
                $this->blockFactory->createTitleBlock(
                    $this->cellByValue($transaction->description),
                    $this->cellByValue($transaction->amount),
                    $transaction->currency ?? BlockFactory::DEFAULT_CURRENCY
                )
            )
            ->chain(
                $this->blockFactory->createStringBlock($this->cell(
                    'Credit Card',
                    \htmlspecialchars_decode($transaction->cardName ?? '')
                ))
            )
            ->chain(
                $this->blockFactory->createStringBlock($this->cell(
                    'Category',
                    \htmlspecialchars_decode($transaction->category ?? '')
                ))
            )
            ->chain(
                $this->blockFactory->createBalanceBlock(
                    \array_merge(
                        $this->cell(
                            'Points',
                            $transaction->miles
                        ),
                        [
                            'multiplier' => $transaction->multiplier,
                            'currency' => $transaction->currency ?? BlockFactory::DEFAULT_CURRENCY,
                            'pointsValue' => $transaction->pointsValue,
                        ]
                    )
                )
            )
            ->chain($this->createEarningPotentialBlock($transaction))
            ->toArray();

        return $result;
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
