<?php

declare(strict_types=1);

namespace AwardWallet\MainBundle\Service\AccountHistory\Formatter;

use AwardWallet\MainBundle\Globals\Localizer\LocalizeService;
use AwardWallet\MainBundle\Service\AccountHistory\Formatter\MobileHistoryFormatter\BlockFactory;
use AwardWallet\MainBundle\Service\AccountHistory\Formatter\MobileHistoryFormatter\Blocks\Block;
use AwardWallet\MainBundle\Service\AccountHistory\Formatter\MobileHistoryFormatter\Blocks\Date;
use AwardWallet\MainBundle\Service\AccountHistory\Formatter\MobileHistoryFormatter\Blocks\Row;
use AwardWallet\MainBundle\Service\AccountHistory\HistoryQuery;
use AwardWallet\MainBundle\Service\AccountHistory\HistoryService;

use function AwardWallet\MainBundle\Globals\Utils\iterFluent\it;

class MobileHistoryFormatter implements HistoryFormatterInterface
{
    private LocalizeService $localizer;

    private BlockFactory $blockFactory;

    public function __construct(
        LocalizeService $localizer,
        BlockFactory $blockFactory
    ) {
        $this->localizer = $localizer;
        $this->blockFactory = $blockFactory;
    }

    public function format(array $rows, HistoryQuery $historyQuery): ?array
    {
        $nextPageToken = $historyQuery->getNextPageToken();

        if ($nextPageToken) {
            $clientOldestYearMonth = $nextPageToken->getPostingDate()->format('Y-m');
        } else {
            $clientOldestYearMonth = null;
        }

        return it($rows)
            ->map(function ($row) use ($historyQuery) { return $this->formatRow($row, $historyQuery); })
            ->groupAdjacentByColumn('dateYM') // group by month
            ->flatMapIndexed(function (array $monthRows, int $monthIndex) use ($clientOldestYearMonth): iterable {
                $firstRowInMonth = $monthRows[0];

                if (
                    // insert date block between months
                    ($monthIndex > 0)
                    // insert the topmost date block on page in case of:
                    //     * this is the first page
                    //
                    //     * the year-month pairs of the earliest row from first
                    //       page and latest row from second page are differ.
                    || ($clientOldestYearMonth !== $firstRowInMonth['dateYM'])
                ) {
                    yield $this->blockFactory->createDateBlock($firstRowInMonth['date']);
                }

                foreach ($monthRows as $monthRow) {
                    yield $this->createRow($monthRow);
                }
            })
            ->toArray();
    }

    public function getId(): string
    {
        return HistoryFormatterInterface::MOBILE;
    }

    public function formatRow(array $row, HistoryQuery $historyQuery): array
    {
        // convert to assoc array for convenience
        $row['cells'] = it($row['cells'])
            ->reindex(function (array $cell) {
                if ('Info' === $cell['field']) {
                    return 'Info[' . $cell['column'] . ']';
                } else {
                    return $cell['field'];
                }
            })
            ->toArrayWithKeys();

        $row['date'] = $date = new \DateTime($row['cells']['PostingDate']['value']);
        // create keys for grouping
        $row['dateYM'] = $date->format('Y-m');

        return $row;
    }

    private function createRow(array $row): Row
    {
        $rowBlock = new Row();
        $rowBlock->style = $row['isPositiveTransaction'] ? 'positive' : 'negative';
        $rowBlock->date = $this->blockFactory->createDatePart($row['date']);
        $cells = $row['cells'];
        $rowBlock->blocks =
            it(
                $this->blockFactory->createTitleBlock(
                    $cells['Description'] ?? $cells['Note'] ?? null,
                    $cells['Amount'] ?? $cells['AmountBalance'] ?? null,
                    $row['currency']
                )
            )
            ->chain($this->createOrderedBlocks($cells))
            ->chain($this->blockFactory->createStringBlock($cells['Category'] ?? null))
            ->chain(
                $this->blockFactory->createEarningPotentialBlock(
                    $cells[HistoryService::EARNING_POTENTIAL_COLUMN] ?? null,
                    $row['uuid'] ?? null
                )
            )
            ->toArray();

        return $rowBlock;
    }

    /**
     * @return iterable<Block>
     */
    private function createOrderedBlocks(array $cells): iterable
    {
        return
            it($cells)
            ->filterHasNotEmptyColumnString('value')
            ->filterNotByKeyInArray([
                // unset custom-ordered cells
                'Description',
                'Note',
                'Amount',
                'AmountBalance',
                'Category',
                HistoryService::EARNING_POTENTIAL_COLUMN,
                'PostingDate',
            ])
            ->flatMapIndexed(function (array $cell, string $cellCode): iterable {
                if (\in_array($cellCode, ['Miles', 'MilesBalance'], true)) {
                    return $this->blockFactory->createBalanceBlock($cell);
                } else {
                    return $this->blockFactory->createStringBlock($cell);
                }
            });
    }
}
