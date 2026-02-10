<?php

namespace AwardWallet\MainBundle\Service\CreditCards;

use Doctrine\DBAL\Connection;

class CreditCardQueries
{
    public const HISTORY_EARLY_POSTINGDATE = 1;
    public const HISTORY_LATE_POSTINGDATE = 2;

    /** @var Connection */
    private $clickHouseService;

    /** @var Connection */
    private $clickHouse;

    public function __construct(
        ClickHouseService $clickHouseService,
        Connection $clickhouseConnection
    ) {
        $this->clickHouseService = $clickHouseService;
        $this->clickHouse = $clickhouseConnection;
    }

    public function fetchSubAccountCards(array $cardsId, ?array $userIds = null): array
    {
        return $this->clickHouse->executeQuery("
            SELECT
                    DISTINCT a.UserID, a.AccountID AS AccountID, a.SuccessCheckDate,
                    sa.SubAccountID, sa.CreditCardID
            FROM {$this->clickHouseService->getActiveDbName()}.SubAccount sa
            JOIN {$this->clickHouseService->getActiveDbName()}.Account a ON (sa.AccountID = a.AccountID) 
            WHERE 
                    sa.CreditCardID IN ({$this->filterArrayIn($cardsId)})
                    {$this->filterByUserId($userIds)}
            ORDER BY a.SuccessCheckDate DESC
        ")->fetchAllAssociative();
    }

    public function fetchDetectedCards(array $cardsId, ?array $userIds = null): array
    {
        return $this->clickHouse->executeQuery("
            SELECT
                    DISTINCT a.UserID, a.AccountID AS AccountID, a.SuccessCheckDate,
                    dc.CreditCardID
            FROM {$this->clickHouseService->getActiveDbName()}.DetectedCards dc
            JOIN {$this->clickHouseService->getActiveDbName()}.Account a ON (dc.AccountID = a.AccountID) 
            WHERE
                    dc.CreditCardID IN ({$this->filterArrayIn($cardsId)})
                    {$this->filterByUserId($userIds)}
            ORDER BY a.SuccessCheckDate DESC
        ")->fetchAllAssociative();
    }

    public function fetchAccountHistoryCards(array $cardsId, ?array $userIds = null): array
    {
        return $this->clickHouse->executeQuery("
            SELECT
                    a.UserID, a.AccountID AS AccountID,
                    ah.CreditCardID, MAX(ah.PostingDate) AS SuccessCheckDate
            FROM {$this->clickHouseService->getActiveDbName()}.AccountHistory ah
            JOIN {$this->clickHouseService->getActiveDbName()}.Account a ON (a.AccountID = ah.AccountID) 
            WHERE 
                    ah.CreditCardID IN ({$this->filterArrayIn($cardsId)})
                    {$this->filterByUserId($userIds)}
            GROUP BY a.UserID, ah.CreditCardID, a.AccountID, a.SuccessCheckDate
            ORDER BY a.SuccessCheckDate DESC
        ")->fetchAllAssociative();
    }

    public function fetchAccountHistorySubAccountDate(string $funcDate, array $userIds): array
    {
        switch ($funcDate) {
            case self::HISTORY_EARLY_POSTINGDATE:
                $dataField = 'MIN(ah.PostingDate)';

                break;

            case self::HISTORY_LATE_POSTINGDATE:
                $dataField = 'MAX(ah.PostingDate)';

                break;

            default:
                throw new \InvalidArgumentException('Undefined type');
        }

        $rows = $this->clickHouse->executeQuery(
            "
            SELECT a.UserID, sa.CreditCardID, $dataField as _dataField
            FROM {$this->clickHouseService->getActiveDbName()}.AccountHistory ah
            JOIN {$this->clickHouseService->getActiveDbName()}.SubAccount sa ON (sa.AccountID = ah.AccountID AND sa.SubAccountID = ah.SubAccountID)
            JOIN {$this->clickHouseService->getActiveDbName()}.Account a ON (a.AccountID = ah.AccountID AND a.AccountID = sa.AccountID)
            WHERE
                    a.UserID IN ({$this->filterArrayIn($userIds)})
                AND ah.SubAccountID IS NOT NULL
                AND ah.PostingDate IS NOT NULL
            GROUP BY a.UserID, sa.CreditCardID
        ")->fetchAllAssociative();

        $result = [];

        foreach ($rows as $row) {
            $userId = $row['a.UserID'];
            $cardId = $row['sa.CreditCardID'];

            array_key_exists($userId, $result) ?: $result[$userId] = [];
            array_key_exists($cardId, $result[$userId]) ?: $result[$userId][$cardId] = [];

            $result[$userId][$cardId] = [
                'PostingDate' => $row['_dataField'],
            ];
        }

        return $result;
    }

    public function fetchAccountHistoryAccountDetectedCardsDate(string $funcDate, array $userIds): array
    {
        switch ($funcDate) {
            case self::HISTORY_EARLY_POSTINGDATE:
                $dataField = 'MIN(ah.PostingDate)';

                break;

            case self::HISTORY_LATE_POSTINGDATE:
                $dataField = 'MAX(ah.PostingDate)';

                break;

            default:
                throw new \InvalidArgumentException('Undefined type');
        }

        $rows = $this->clickHouse->executeQuery(
            "
            SELECT a.UserID, sa.CreditCardID, $dataField as _dataField
            FROM {$this->clickHouseService->getActiveDbName()}.AccountHistory ah
            JOIN {$this->clickHouseService->getActiveDbName()}.SubAccount sa ON (sa.AccountID = ah.AccountID AND sa.SubAccountID = ah.SubAccountID)
            JOIN {$this->clickHouseService->getActiveDbName()}.Account a ON (a.AccountID = ah.AccountID AND a.AccountID = sa.AccountID)
            WHERE
                    a.UserID IN ({$this->filterArrayIn($userIds)})
                AND ah.SubAccountID IS NULL
                AND ah.PostingDate IS NOT NULL
            GROUP BY a.UserID, sa.CreditCardID
        ")->fetchAllAssociative();

        $result = [];

        foreach ($rows as $row) {
            $userId = $row['a.UserID'];
            $cardId = $row['sa.CreditCardID'];

            array_key_exists($userId, $result) ?: $result[$userId] = [];
            array_key_exists($cardId, $result[$userId]) ?: $result[$userId][$cardId] = [];

            $result[$userId][$cardId] = [
                'PostingDate' => $row['_dataField'],
            ];
        }

        return $result;
    }

    public function fetchAccountHistoryDate(string $funcDate, array $userIds): array
    {
        switch ($funcDate) {
            case self::HISTORY_EARLY_POSTINGDATE:
                $dataField = 'MIN(PostingDate)';

                break;

            case self::HISTORY_LATE_POSTINGDATE:
                $dataField = 'MAX(PostingDate)';

                break;

            default:
                throw new \InvalidArgumentException('Undefined type');
        }

        $rows = $this->clickHouse->fetchAllAssociative("
            SELECT
                    a.UserID,
                    ah.CreditCardID,
                    {$dataField} AS _dataField
            FROM {$this->clickHouseService->getActiveDbName()}.AccountHistory ah
            JOIN {$this->clickHouseService->getActiveDbName()}.Account a ON (a.AccountID = ah.AccountID)
            WHERE
                    a.UserID IN ({$this->filterArrayIn($userIds)})
            GROUP BY a.UserID, ah.CreditCardID
        ");

        $result = [];

        foreach ($rows as $row) {
            $userId = $row['UserID'];
            $cardId = $row['CreditCardID'];

            array_key_exists($userId, $result) ?: $result[$userId] = [];
            array_key_exists($cardId, $result[$userId]) ?: $result[$userId][$cardId] = [];

            $result[$userId][$cardId] = [
                'PostingDate' => $row['_dataField'],
            ];
        }

        return $result;
    }

    public function fetchAllCardsUsers(array $cardIds, ?array $userIds = null): array
    {
        $db = $this->clickHouseService->getActiveDbName();
        $cardsId = $this->filterArrayIn($cardIds);
        $userFilter = $this->filterByUserId($userIds);

        return $this->clickHouse->fetchAllAssociative("
            SELECT
                DISTINCT UserID, CreditCardID
            FROM (
                SELECT a.UserID, sa.CreditCardID
                FROM {$db}.SubAccount sa
                JOIN {$db}.Account a ON (sa.AccountID = a.AccountID) 
                WHERE 
                        sa.CreditCardID IN ({$cardsId})
                        {$userFilter}

                UNION ALL

                SELECT a.UserID, ah.CreditCardID
                FROM {$db}.AccountHistory ah
                JOIN {$db}.SubAccount sa ON (ah.SubAccountID = sa.SubAccountID)
                JOIN {$db}.Account a ON (sa.AccountID = a.AccountID)
                WHERE 
                        ah.CreditCardID IN ({$cardsId})
                        {$userFilter}

                UNION ALL

                SELECT a.UserID, dc.CreditCardID
                FROM {$db}.DetectedCards dc
                JOIN {$db}.Account a ON (dc.AccountID = a.AccountID) 
                WHERE
                        dc.CreditCardID IN ({$cardsId})
                        {$userFilter}
            ) matched
        ");
    }

    private function filterArrayIn(array $values): string
    {
        if (empty($values)) {
            throw new \Exception('array $values should not be empty');
        }
        $values = array_map('intval', $values);
        $values = array_unique($values);

        return implode(',', $values);
    }

    private function filterByUserId(?array $userIds = null): string
    {
        if (empty($userIds)) {
            return '';
        }

        return 'AND a.UserID IN (' . implode(',', $userIds) . ')';
    }
}
