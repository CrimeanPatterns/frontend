<?php

namespace AwardWallet\MainBundle\Service\AccountHistory\Formatter\MobileHistoryFormatter;

use AwardWallet\MainBundle\Globals\Localizer\LocalizeService;
use AwardWallet\MainBundle\Globals\StringUtils;
use AwardWallet\MainBundle\Service\AccountHistory\Formatter\MobileHistoryFormatter\Blocks\Block;
use AwardWallet\MainBundle\Service\AccountHistory\Formatter\MobileHistoryFormatter\Blocks\CashEquivalent;
use AwardWallet\MainBundle\Service\AccountHistory\Formatter\MobileHistoryFormatter\Blocks\Date;
use AwardWallet\MainBundle\Service\AccountHistory\Formatter\MobileHistoryFormatter\Blocks\EarningPotential;
use AwardWallet\MainBundle\Service\AccountHistory\Formatter\MobileHistoryFormatter\Blocks\Kind;
use AwardWallet\MainBundle\Service\AccountHistory\Formatter\MobileHistoryFormatter\Blocks\TotalsTitle;
use AwardWallet\MainBundle\Service\AccountHistory\HistoryService;
use AwardWallet\MainBundle\Service\AccountHistory\TransactionTotals;

class BlockFactory
{
    public const DEFAULT_CURRENCY = 'USD';

    private LocalizeService $localizer;

    public function __construct(
        LocalizeService $localizer
    ) {
        $this->localizer = $localizer;
    }

    /**
     * @return iterable<Block>
     */
    public function createStringBlock(?array $cell): iterable
    {
        if (
            $cell
            && StringUtils::isNotEmpty($cell['value'])
            && StringUtils::isNotEmpty($cell['column'])
        ) {
            $block = new Block(Kind::KIND_STRING);
            $block->name = \html_entity_decode((string) $cell['column']) . ':';
            $block->value = \html_entity_decode((string) $cell['value']);

            yield $block;
        }
    }

    /**
     * @return iterable<Block>
     */
    public function createTitleBlock(?array $titleCell, ?array $amountCell, ?string $currency): iterable
    {
        if (!$titleCell) {
            return;
        }

        $title = StringUtils::isNotEmpty($titleCell['value']) ?
            \html_entity_decode((string) $titleCell['value']) :
            null;

        if (
            $amountCell
            && StringUtils::isNotEmpty($amountCell['value'] ?? '')
        ) {
            $value = $this->localizer->formatCurrency((float) $amountCell['value'], $currency);
        } else {
            $value = null;
        }

        if (!isset($title) && !isset($value)) {
            return;
        }

        $titleBlock = new Block(Kind::KIND_TITLE);
        $titleBlock->name = $title;
        $titleBlock->value = $value;

        yield $titleBlock;
    }

    /**
     * @return iterable<EarningPotential>
     */
    public function createEarningPotentialBlock(?array $earningPotentialCell, ?string $uuid, ?string $forcedSign = null, ?int $fraction = null): iterable
    {
        if (!$earningPotentialCell) {
            return;
        }

        switch ($earningPotentialCell['type']) {
            case HistoryService::FIELD_TYPE_EMPTY:
                return;

            case HistoryService::FIELD_TYPE_THUMB_UP:
                $earningPotentialBlock = new EarningPotential();
                $earningPotentialBlock->type = 'thumb_up';
                $earningPotentialBlock->name = $earningPotentialCell['column'] . ':';

                yield $earningPotentialBlock;

                return;

            case HistoryService::FIELD_TYPE_OFFER:
                $earningPotentialBlock = new EarningPotential();
                $earningPotentialBlock->type = 'offer';
                $earningPotentialBlock->name = $earningPotentialCell['column'] . ':';

                if ($earningPotentialCell['value'] > 0) {
                    $earningPotentialBlock->value = $this->formatWithSign($earningPotentialCell['value'], null, $forcedSign, $fraction);
                }

                if (($earningPotentialCell['multiplier'] ?? 0) > 1) {
                    $earningPotentialBlock->multiplier = $this->localizer->formatNumber((float) $earningPotentialCell['multiplier']) . 'x';
                }

                if (StringUtils::isNotEmpty($earningPotentialCell['color'] ?? '')) {
                    $earningPotentialBlock->color = \html_entity_decode((string) $earningPotentialCell['color']);
                }

                if (StringUtils::isNotEmpty($earningPotentialCell['type'] ?? '')) {
                    $earningPotentialBlock->type = \html_entity_decode((string) $earningPotentialCell['type']);
                }

                $earningPotentialBlock->uuid = $uuid;

                if (isset($earningPotentialCell['currency'])) {
                    if (isset($earningPotentialCell['pointsValue'])) {
                        $earningPotentialBlock->pointsValue = $this->localizer->formatCurrency(
                            $earningPotentialCell['pointsValue'],
                            $earningPotentialCell['currency']
                        );
                    }

                    if (isset($earningPotentialCell['pointsValueRange'])) {
                        [$pointsValueMin, $pointsValueMax] = $earningPotentialCell['pointsValueRange'];
                        $earningPotentialBlock->pointsValueRange = [
                            $this->localizer->formatCurrency(
                                $pointsValueMin,
                                $earningPotentialCell['currency']
                            ),
                            $this->localizer->formatCurrency(
                                $pointsValueMax,
                                $earningPotentialCell['currency']
                            ),
                        ];
                    }
                }

                yield $earningPotentialBlock;

                return;
        }
    }

    public function createCashEquivalentBlock(
        ?array $cell,
        ?string $uuid
    ): iterable {
        if (!$cell) {
            return;
        }

        $cashEquivalentBlock = new CashEquivalent();

        foreach ($cell as $key => $value) {
            if (property_exists($cashEquivalentBlock, $key)) {
                $cashEquivalentBlock->{$key} = $value;
            }
        }

        if ($cell['value'] > 0) {
            $cashEquivalentBlock->value = $this->localizer->formatCurrency($cell['value'], $cell['currency']);
        }

        $cashEquivalentBlock->uuid = $uuid;

        yield $cashEquivalentBlock;
    }

    public function formatWithSign($value, ?string $currency = null, ?string $forcedSign = null, ?int $fraction = null): string
    {
        $value = (float) $value;

        if (isset($forcedSign)) {
            $sign = $forcedSign;
        } else {
            $sign = $value > 0 ? '+' : '';
        }

        $value = isset($currency) ?
            $this->localizer->formatCurrency($value, $currency) :
            $this->localizer->formatNumber($value, $fraction);

        return $sign . $value;
    }

    public function createDateBlock(\DateTime $dateTime): Date
    {
        $block = new Date();
        $block->value =
            trim(mb_strtoupper($this->localizer->patternDateTime($dateTime, 'LLLL')), '.') .
            ' ' .
            $this->localizer->patternDateTime($dateTime, 'yyyy');

        return $block;
    }

    /**
     * @return iterable<TotalsTitle>
     */
    public function createTotalsTitleBlock(TransactionTotals $totals): iterable
    {
        if (!$totals->transactions) {
            return;
        }

        $block = new TotalsTitle();
        $block->amount = $this->localizer->formatCurrency($totals->amount, $totals->currency ?? self::DEFAULT_CURRENCY);
        $block->transactions = $this->localizer->formatNumber($totals->transactions);
        $block->transactionsTitle = 'Transactions';
        $block->average = $this->localizer->formatCurrency($totals->average, $totals->currency ?? self::DEFAULT_CURRENCY);
        $block->averageTitle = 'Average';

        yield $block;
    }

    /**
     * @return iterable<Block>
     */
    public function createBalanceBlock(?array $cell, ?string $forcedSign = null, ?string $namePostFix = null, ?int $fraction = null): iterable
    {
        if (!$cell) {
            return;
        }

        $pointsBlock = new Block(Kind::KIND_BALANCE);
        $pointsBlock->name = $cell['column'] . ($namePostFix ?? ':');
        $pointsBlock->value = $this->formatWithSign($cell['value'], null, $forcedSign, $fraction);

        if (($cell['multiplier'] ?? 0) >= 1) {
            $pointsBlock->multiplier = $this->localizer->formatNumber((float) $cell['multiplier']) . 'x';
        }

        if (isset($cell['currency'])) {
            if (isset($cell['pointsValue'])) {
                $pointsBlock->pointsValue = $this->localizer->formatCurrency($cell['pointsValue'], $cell['currency']);
            }

            if (isset($cell['pointsValueRange'])) {
                [$pointsValueMin, $pointsValueMax] = $cell['pointsValueRange'];

                $pointsBlock->pointsValueRange = [
                    $this->localizer->formatCurrency($pointsValueMin, $cell['currency']),
                    $this->localizer->formatCurrency($pointsValueMax, $cell['currency']),
                ];
            }
        }

        yield $pointsBlock;
    }

    public function createDatePart(\DateTime $dateTime): array
    {
        return [
            'd' => $this->localizer->patternDateTime($dateTime, 'd'),
            'm' => trim(mb_strtoupper($this->localizer->patternDateTime($dateTime, 'LLL')), '.'),
        ];
    }
}
