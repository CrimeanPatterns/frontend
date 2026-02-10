<?php

namespace AwardWallet\MainBundle\Service\CreditCards;

use AwardWallet\MainBundle\Entity\QsTransaction;
use Doctrine\DBAL\Connection;

class QsTransactionData
{
    public const QS_TYPE_AW = 1;
    public const QS_TYPE_AT101 = 2;

    public const QS_TYPES = [
        self::QS_TYPE_AW => 'aw',
        self::QS_TYPE_AT101 => 'at',
    ];

    public const QS_TYPES_TITLE = [
        self::QS_TYPE_AW => 'AwardWallet',
        self::QS_TYPE_AT101 => 'AT 101',
    ];

    /** @var Connection */
    private $connection;

    public function __construct(Connection $connection)
    {
        $this->connection = $connection;
    }

    public function fetchByType(int $qsType, ?\DateTime $setDay = null): ?array
    {
        $typesCondition = $this->getTypesCondition();

        if (self::QS_TYPE_AW === $qsType) {
            $queryTypeCondition = $typesCondition[self::QS_TYPES[self::QS_TYPE_AW]];
        } elseif (self::QS_TYPE_AT101 === $qsType) {
            $queryTypeCondition = $typesCondition[self::QS_TYPES[self::QS_TYPE_AT101]];
        } else {
            throw new \Exception('Unknown QS Type [' . $qsType . ']');
        }

        $baseQuery = '
            SELECT
                    SUM(t.Clicks) AS sumClicks,
                    SUM(t.Applications) AS sumApplications,
                    SUM(t.Approvals) AS sumApprovals,
                    SUM(t.Earnings) AS sumEarnings,
                    SUM(IF(t.Approvals > 0, t.Earnings, 0)) AS sumApprovalEarnings
            FROM QsTransaction t
        ';

        $lastAvailableDay = $this->connection->fetchColumn('SELECT ClickDate FROM QsTransaction ORDER BY ClickDate DESC LIMIT 1');

        if (empty($lastAvailableDay)) {
            return null;
        }

        $day = $setDay ?? new \DateTime('@' . strtotime($lastAvailableDay));
        $result = [
            'interval' => ['day' => null, 'month' => null],
            'totals' => ['day' => [], 'month' => []],
            'lastDay' => $day,
        ];

        $result['interval']['day'] = [
            'begin' => date('Y-m-d 00:00', $day->getTimestamp()),
            'end' => date('Y-m-d 23:59:59', $day->getTimestamp()),
        ];
        $betweenDay = 'BETWEEN ' . $this->connection->quote($result['interval']['day']['begin']) . ' AND ' . $this->connection->quote($result['interval']['day']['end']);
        $result['totals']['day'] = $this->connection->fetchAssociative(
            $baseQuery
            . 'WHERE 
                    (ProcessDate ' . $betweenDay . ' OR (ClickDate ' . $betweenDay . ' AND ProcessDate IS NULL))
                AND (' . implode(' OR ', $queryTypeCondition) . ')
        ');

        $result['interval']['month'] = [
            'begin' => date('Y-m-01', $day->getTimestamp()),
            'end' => date('Y-m-t', $day->getTimestamp()),
        ];
        $betweenMonth = 'BETWEEN ' . $this->connection->quote($result['interval']['month']['begin']) . ' AND ' . $this->connection->quote($result['interval']['month']['end']);
        $result['totals']['month'] = $this->connection->fetchAssociative(
            $baseQuery
            . 'WHERE
                    (ProcessDate ' . $betweenMonth . ' OR (ClickDate ' . $betweenMonth . ' AND ProcessDate IS NULL))
                AND (' . implode(' OR ', $queryTypeCondition) . ')'
        );

        return $result;
    }

    public function getTypesCondition(): array
    {
        return [
            self::QS_TYPES[self::QS_TYPE_AW] => [
                '(Account = ' . QsTransaction::ACCOUNT_DIRECT . " AND (Source <> '101' OR Source IS NULL))",
                '(Account = ' . QsTransaction::ACCOUNT_DIRECT . " AND Source IS NULL)",
                '(Account = ' . QsTransaction::ACCOUNT_CARDRATINGS . " AND (Source <> '101' OR Source IS NULL))",
            ],
            self::QS_TYPES[self::QS_TYPE_AT101] => [
                '(Account = ' . QsTransaction::ACCOUNT_DIRECT . " AND Source = '101')",
                '(Account = ' . QsTransaction::ACCOUNT_AWARDTRAVEL101 . ')',
                '(Account = ' . QsTransaction::ACCOUNT_CARDRATINGS . " AND Source = '101')",
            ],
        ];
    }
}
