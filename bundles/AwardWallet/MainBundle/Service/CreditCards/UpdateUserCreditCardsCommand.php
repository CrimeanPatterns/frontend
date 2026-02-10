<?php

namespace AwardWallet\MainBundle\Service\CreditCards;

use AwardWallet\MainBundle\Entity\UserCreditCard;
use AwardWallet\MainBundle\FrameworkExtension\Command;
use Doctrine\DBAL\Connection;
use Psr\Log\LoggerInterface;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class UpdateUserCreditCardsCommand extends Command
{
    private const CARDS_PER_REQUEST = 500;
    private const INSERT_PER_REQUEST = 1000;
    private const ACCOUNTHISTORY_PER_REQUEST = 1500;

    private const FIELD_DETECTEDVIABANK = 'DetectedViaBank';
    private const FIELD_DETECTEDVIACOBRAND = 'DetectedViaCobrand';
    private const FIELD_DETECTEDVIAQS = 'DetectedViaQS';
    private const FIELD_DETECTEDVIAEMAIL = 'DetectedViaEmail';

    protected static $defaultName = 'aw:update-user-creditcard';

    private LoggerInterface $logger;
    private CreditCardQueries $creditCardQueries;
    private Connection $connection;
    private array $creditCardIds = [];
    private array $existsUserCards = [];
    private array $existsUserCardsViaBank = [];
    private array $closedUserCards = [];
    private OutputInterface $output;
    private ClickHouseService $clickHouseService;
    private Connection $clickHouse;
    private UserCreditCardsUtil $userCreditCardsUtil;

    private array $userIds = [];

    public function __construct(
        LoggerInterface $logger,
        Connection $connection,
        CreditCardQueries $creditCardQueries,
        ClickHouseService $clickHouseService,
        Connection $clickhouseConnection,
        UserCreditCardsUtil $userCreditCardsUtil
    ) {
        parent::__construct();
        $this->logger = $logger;
        $this->creditCardQueries = $creditCardQueries;
        $this->connection = $connection;
        $this->clickHouseService = $clickHouseService;
        $this->clickHouse = $clickhouseConnection;
        $this->userCreditCardsUtil = $userCreditCardsUtil;
    }

    protected function configure(): void
    {
        $this->setDescription('Detecting credit cards that users have');
    }

    protected function execute(InputInterface $input, OutputInterface $output): void
    {
        $this->output = $output;
        $this->creditCardIds = $this->connection->fetchFirstColumn('SELECT CreditCardID FROM CreditCard');
        $this->existsUserCards = $this->getExistsUserCards();
        $this->existsUserCardsViaBank = $this->getExistsUserCardsViaBank();
        $this->closedUserCards = $this->getClosedDetectedCards();

        $this->fillFromDetectedCardsSubAccounts();
        $this->fillFromAccountHistory();
        $this->fillFromQsTransactions();

        $this->setEarliestSeenDate(true);
        $this->setEarliestSeenDate(false);
        $this->recheckSeenDate($this->creditCardQueries::HISTORY_EARLY_POSTINGDATE);
        $this->recheckSeenDate($this->creditCardQueries::HISTORY_LATE_POSTINGDATE);

        $this->updateEarliestSeenDateFromClickHouse();
        $this->updateEarliestSeenDateFromClickHouse2();

        $this->setClosedCards();
        $this->postProcess();

        $this->removeFilteredEntries();
    }

    private function insertData(array $data, int $sourcePlace, string $detectedField): void
    {
        $affected = 0;

        foreach (array_chunk($data, self::INSERT_PER_REQUEST) as $userCard) {
            if (self::FIELD_DETECTEDVIACOBRAND !== $detectedField) {
                $userCard = $this->userCreditCardsUtil->filterAccounts($userCard);
            }

            $sqlInsert = '';
            $batchSize = 0;

            foreach ($userCard as $rowUserCard) {
                $accountId = empty($rowUserCard['AccountID']) ? 'NULL' : $rowUserCard['AccountID'];
                $subAccountId = empty($rowUserCard['SubAccountID']) ? 'NULL' : $rowUserCard['SubAccountID'];
                $values = '(' . $rowUserCard['UserID'] . ',' . $rowUserCard['CreditCardID'] . ',' . $this->quote($rowUserCard['SuccessCheckDate']) . ', 1, ' . $accountId . ', ' . $subAccountId . ', ' . $sourcePlace . ')';

                $hasClosedCardKey = $accountId . '_' . $subAccountId . '_' . $rowUserCard['CreditCardID'];

                if (array_key_exists($hasClosedCardKey, $this->closedUserCards)) {
                    continue;
                }

                if (isset($this->existsUserCards[$rowUserCard['UserID']][$rowUserCard['CreditCardID']])) {
                    if (self::FIELD_DETECTEDVIACOBRAND !== $detectedField
                        || !isset($this->existsUserCardsViaBank[$rowUserCard['UserID']][$rowUserCard['CreditCardID']])
                    ) {
                        unset($this->existsUserCards[$rowUserCard['UserID']][$rowUserCard['CreditCardID']]);
                    }
                }

                $date = empty($rowUserCard['SuccessCheckDate']) ? 'NULL' : $this->quote($rowUserCard['SuccessCheckDate']);
                $lastSeenDate = 'LastSeenDate = CASE WHEN ' . $date . ' > LastSeenDate THEN ' . $date . ' ELSE LastSeenDate END,';

                if (self::FIELD_DETECTEDVIACOBRAND === $detectedField) {
                    $lastSeenDate = 'LastSeenDate = ' . $date . ',';
                }

                $sqlInsert .= '
                    INSERT IGNORE INTO UserCreditCard (UserID, CreditCardID, LastSeenDate, ' . $detectedField . ', AccountID, SubAccountID, SourcePlace)
                    VALUES ' . $values . '
                        ON DUPLICATE KEY UPDATE
                            ' . $lastSeenDate . '
                            AccountID = ' . $accountId . ',
                            SubAccountID = ' . $subAccountId . ',
                            SourcePlace = ' . $sourcePlace . ',
                            ' . $detectedField . ' = 1,
                            IsClosed = 0
                ; ';
                $batchSize++;

                if ($batchSize > 50) {
                    $result = $this->connection->executeStatement($sqlInsert);
                    $affected += $result;
                    $sqlInsert = '';
                    $batchSize = 0;
                }
            }

            if ($batchSize > 0) {
                $affected += $this->connection->executeStatement($sqlInsert);
            }
        }
        $this->output->write($affected . ', ');
    }

    private function fillFromDetectedCardsSubAccounts(): void
    {
        $this->output->writeln(__FUNCTION__);
        $this->output->write(' - affected rows: ');

        foreach (array_chunk($this->creditCardIds, self::CARDS_PER_REQUEST) as $creditCardIds) {
            $subAccountCards = $this->creditCardQueries->fetchSubAccountCards($creditCardIds, $this->userIds);
            $this->insertData($subAccountCards, UserCreditCard::SOURCE_PLACE_SUBACCOUNT, self::FIELD_DETECTEDVIABANK);

            $detectedCards = $this->creditCardQueries->fetchDetectedCards($creditCardIds, $this->userIds);
            $this->insertData($detectedCards, UserCreditCard::SOURCE_PLACE_DETECTED_CARDS, self::FIELD_DETECTEDVIABANK);
        }
        $this->output->writeln('');
    }

    private function fillFromAccountHistory(): void
    {
        $this->output->writeln(__FUNCTION__);
        $this->output->write(' - affected rows: ');

        foreach (array_chunk($this->creditCardIds, self::CARDS_PER_REQUEST) as $creditCardIds) {
            $userCards = $this->creditCardQueries->fetchAccountHistoryCards($creditCardIds, $this->userIds);
            $this->insertData($userCards, UserCreditCard::SOURCE_PLACE_ACCOUNT_HISTORY, self::FIELD_DETECTEDVIACOBRAND);
        }
        $this->output->writeln('');
    }

    private function fillFromQsTransactions(): void
    {
        $this->output->writeln(__FUNCTION__);
        $transactions = $this->connection->fetchAllAssociative('
            SELECT
                u.UserID,
                cc.CreditCardID,
                qs.QsCreditCardID, qs.ProcessDate AS SuccessCheckDate
            FROM QsTransaction qs
            JOIN Usr u ON (u.RefCode = qs.RefCode)
            JOIN CreditCard cc ON (qs.QsCreditCardID = cc.QsCreditCardID)
            -- LEFT JOIN UserCreditCard ucc ON (ucc.UserID = u.UserID AND ucc.CreditCardID = cc.CreditCardID AND ucc.DetectedViaQS = 1)
            WHERE
                    ' . (empty($this->userIds) ? '' : 'u.UserID IN (' . implode(',', $this->userIds) . ') AND ') . '
                    -- ucc.UserCreditCardID IS NULL AND 
                    qs.RefCode IS NOT NULL
                AND qs.Approvals = 1
        ');
        $this->output->writeln(' found: ' . \count($transactions));
        $this->output->write(' - affected rows: ');

        $this->insertData($transactions, UserCreditCard::SOURCE_PLACE_QS_TRANSACTION, self::FIELD_DETECTEDVIAQS);

        $this->output->writeln('');
    }

    private function setEarliestSeenDate($fromSubAccount): void
    {
        $this->output->writeln(__FUNCTION__);

        $affected = 0;
        $userCredits = $this->connection->fetchAll('
            SELECT UserCreditCardID, UserID, CreditCardID
            FROM UserCreditCard
            WHERE
                    ' . (empty($this->userIds) ? '' : 'UserID IN (' . implode(',', $this->userIds) . ') AND ') . '
                    EarliestSeenDate IS NULL
                AND ' . self::FIELD_DETECTEDVIABANK . ' = 1
                AND IsClosed = 0'
        );

        if (empty($userCredits)) {
            $this->output->writeln(' - not found');

            return;
        }

        foreach (array_chunk($userCredits, self::ACCOUNTHISTORY_PER_REQUEST) as $userCredits_chunk) {
            $timeStart = microtime(true);

            if ($fromSubAccount) {
                $cardDates = $this->creditCardQueries->fetchAccountHistorySubAccountDate(
                    $this->creditCardQueries::HISTORY_EARLY_POSTINGDATE,
                    array_column($userCredits_chunk, 'UserID')
                );
            } else {
                $cardDates = $this->creditCardQueries->fetchAccountHistoryAccountDetectedCardsDate(
                    $this->creditCardQueries::HISTORY_EARLY_POSTINGDATE,
                    array_column($userCredits_chunk, 'UserID')
                );
            }
            $timeEnd = microtime(true);
            $this->output->writeln('* ' . ($fromSubAccount
                    ? 'fetchAccountHistorySubAccountDate'
                    : 'fetchAccountHistoryAccountDetectedCardsDate') . '() time: ' . round($timeEnd - $timeStart, 2)
            );

            foreach ($userCredits_chunk as $userCredit) {
                $ucUserId = $userCredit['UserID'];
                $ucCreditCardId = $userCredit['CreditCardID'];

                $seenDate = isset($cardDates[$ucUserId][$ucCreditCardId]['PostingDate'])
                    ? $this->quote($cardDates[$ucUserId][$ucCreditCardId]['PostingDate'])
                    : null;
                // : ($fromSubAccount ? null : 'NOW()');

                if (null !== $seenDate) {
                    $affected += $this->connection->executeStatement('
                        UPDATE UserCreditCard
                        SET EarliestSeenDate = CASE 
                                WHEN EarliestSeenDate < ' . $seenDate . ' THEN EarliestSeenDate
                                ELSE ' . $seenDate . ' 
                            END,
                            LastSeenDate = CASE 
                                WHEN LastSeenDate > ' . $seenDate . ' THEN LastSeenDate
                                ELSE ' . $seenDate . '
                            END
                        WHERE UserCreditCardID = ' . $userCredit['UserCreditCardID']
                    );
                } elseif (!$fromSubAccount) {
                    $affected += $this->connection->executeStatement('
                        UPDATE UserCreditCard
                        SET EarliestSeenDate = LastSeenDate
                        WHERE UserCreditCardID = ' . $userCredit['UserCreditCardID']
                    );
                }
            }
        }

        $this->output->writeln(' - affected rows: ' . $affected);
    }

    private function recheckSeenDate($dataType): void
    {
        $this->output->write(__FUNCTION__);

        if ($this->creditCardQueries::HISTORY_EARLY_POSTINGDATE === $dataType) {
            $dataFieldName = 'EarliestSeenDate';
        } elseif ($this->creditCardQueries::HISTORY_LATE_POSTINGDATE === $dataType) {
            $dataFieldName = 'LastSeenDate';
        } else {
            throw new \InvalidArgumentException('Undefined dataType');
        }
        $this->output->writeln(' : ' . $dataFieldName);

        $affected = 0;
        $userCredits = $this->connection->fetchAllAssociative('
            SELECT UserCreditCardID, UserID, CreditCardID
            FROM UserCreditCard
            WHERE
                    ' . (empty($this->userIds) ? '' : 'UserID IN (' . implode(',', $this->userIds) . ') AND ') . '
                    ' . self::FIELD_DETECTEDVIACOBRAND . ' = 1
                AND IsClosed = 0
        ');

        if (empty($userCredits)) {
            $this->output->writeln(' - not found');

            return;
        }

        $cardDates = [];

        foreach (array_chunk($userCredits, 25000) as $userCredits_chunk) {
            $data = $this->creditCardQueries->fetchAccountHistoryDate(
                $dataType,
                array_column($userCredits_chunk, 'UserID')
            );

            $cardDates = ($cardDates + $data);
        }
        /*
        $cardDates = $this->creditCardQueries->fetchAccountHistoryDate(
            $dataType,
            array_column($userCredits, 'UserID')
        );
        */

        $sql = '';
        $batchSize = 0;

        foreach ($userCredits as $userCredit) {
            $ucUserId = $userCredit['UserID'];
            $ucCreditCardId = $userCredit['CreditCardID'];

            $seentDate = isset($cardDates[$ucUserId][$ucCreditCardId]['PostingDate'])
                ? $this->quote($cardDates[$ucUserId][$ucCreditCardId]['PostingDate'])
                : null;

            if (null !== $seentDate) {
                $sql .= '
                    UPDATE UserCreditCard
                    SET EarliestSeenDate = CASE
                            WHEN EarliestSeenDate < ' . $seentDate . ' THEN EarliestSeenDate
                            ELSE ' . $seentDate . '
                        END,
                        LastSeenDate = CASE 
                            WHEN LastSeenDate > ' . $seentDate . ' THEN LastSeenDate
                            ELSE ' . $seentDate . '
                        END
                    WHERE UserCreditCardID = ' . $userCredit['UserCreditCardID'] . '; ';
                $batchSize++;

                if ($batchSize >= 100) {
                    $affected += $this->connection->executeStatement($sql);
                    $sql = '';
                    $batchSize = 0;
                }
            }
        }

        if ($batchSize > 0) {
            $affected += $this->connection->executeStatement($sql);
        }

        $this->output->writeln(' - found: ' . \count($userCredits) . ', affected rows: ' . $affected);
    }

    private function setClosedCards(): void
    {
        $this->output->writeln(__FUNCTION__);

        $nonexistentUserCreditId = [];

        foreach ($this->existsUserCards as $userId => $userCards) {
            foreach ($userCards as $cardId => $userCard) {
                $nonexistentUserCreditId[] = $userCard['UserCreditCardID'];
            }
        }
        $affected = !empty($nonexistentUserCreditId)
            ? $this->connection->executeStatement('
                UPDATE UserCreditCard
                SET IsClosed = 1, ClosedDate = NOW()
                WHERE   
                        ' . self::FIELD_DETECTEDVIAEMAIL . ' = 0
                    AND UserCreditCardID IN (' . implode(',', $nonexistentUserCreditId) . ')
            ')
            : 0;
        $this->output->writeln(' - affected: ' . $affected);
    }

    private function getExistsUserCards(): array
    {
        $rows = $this->connection
            ->fetchAllAssociative('
                SELECT UserCreditCardID, UserID, CreditCardID
                FROM UserCreditCard
                WHERE ' . (empty($this->userIds) ? '1' : 'UserID IN (' . implode(',', $this->userIds) . ')')
            );
        $existsUserCredit = [];

        foreach ($rows as $row) {
            $userId = $row['UserID'];
            $cardId = $row['CreditCardID'];

            array_key_exists($userId, $existsUserCredit) ?: $existsUserCredit[$userId] = [];
            array_key_exists($cardId, $existsUserCredit[$userId]) ?: $existsUserCredit[$userId][$cardId] = [];

            $existsUserCredit[$userId][$cardId] = [
                'UserCreditCardID' => $row['UserCreditCardID'],
            ];
        }

        return $existsUserCredit;
    }

    private function getExistsUserCardsViaBank(): array
    {
        $rows = $this->connection
            ->fetchAllAssociative('
                SELECT UserCreditCardID, UserID, CreditCardID
                FROM UserCreditCard
                WHERE ' . (empty($this->userIds) ? '1' : 'UserID IN (' . implode(',', $this->userIds) . ')') . '
                    AND ' . self::FIELD_DETECTEDVIABANK . ' = 1
            ');
        $existsUserCredit = [];

        foreach ($rows as $row) {
            $userId = $row['UserID'];
            $cardId = $row['CreditCardID'];

            array_key_exists($userId, $existsUserCredit) ?: $existsUserCredit[$userId] = [];
            array_key_exists($cardId, $existsUserCredit[$userId]) ?: $existsUserCredit[$userId][$cardId] = [];

            $existsUserCredit[$userId][$cardId] = [
                'UserCreditCardID' => $row['UserCreditCardID'],
            ];
        }

        return $existsUserCredit;
    }

    private function getClosedDetectedCards(): array
    {
        $rows = $this->connection->fetchAllAssociative("
            SELECT AccountID, SubAccountID, CreditCardID
            FROM DetectedCard
            WHERE
                   Description LIKE 'Cancelled'
                OR Description LIKE 'Closed'
        ");

        $list = [];

        foreach ($rows as $row) {
            if (empty($row['AccountID']) || empty($row['CreditCardID'])) {
                continue;
            }

            $key = $row['AccountID'] . '_' . ($row['SubAccountID'] ?? 'NULL') . '_' . $row['CreditCardID'];
            $list[$key] = true;
        }

        return $list;
    }

    private function updateEarliestSeenDateFromClickHouse(): void
    {
        $this->output->writeln(__FUNCTION__);

        $list = $this->clickHouse->executeQuery("
            SELECT
                    a.UserID, ah.CreditCardID, MIN(PostingDate) AS _minPostingDate
            FROM {$this->clickHouseService->getActiveDbName()}.AccountHistory ah
            JOIN {$this->clickHouseService->getActiveDbName()}.Account a ON (a.AccountID = ah.AccountID)
            WHERE
                    " . (empty($this->userIds) ? '1' : 'a.UserID IN (' . implode(',', $this->userIds) . ')') . "
                AND ah.PostingDate IS NOT NULL
                AND ah.CreditCardID IS NOT NULL
            GROUP BY a.UserID, ah.CreditCardID"
        )->fetchAllAssociative();

        if (empty($list)) {
            $this->output->writeln(' -- not found');

            return;
        }

        $historyRows = [];

        foreach ($list as $row) {
            $historyRows[$row['UserID'] . '-' . $row['CreditCardID']] = strtotime($row['_minPostingDate']);
        }
        unset($list);

        $userCreditCards = $this->connection->fetchAllAssociative('
            SELECT UserCreditCardID, UserID, CreditCardID, EarliestSeenDate
            FROM UserCreditCard
            WHERE ' . (empty($this->userIds) ? '1' : 'UserID IN (' . implode(',', $this->userIds) . ')')
        );

        $sql = '';
        $batchSize = 0;

        foreach ($userCreditCards as $row) {
            $key = $row['UserID'] . '-' . $row['CreditCardID'];

            if (array_key_exists($key, $historyRows) && $historyRows[$key] < strtotime($row['EarliestSeenDate'])) {
                $newDate = date('Y-m-d H:i:s', $historyRows[$key]);
                // $this->output->writeln('userId: ' . $row['UserID'] . "\t" . 'creditCardId: ' . $row['CreditCardID'] . "\t" . 'new date: ' . $newDate);
                $sql .= '
                    UPDATE UserCreditCard
                    SET EarliestSeenDate = CASE WHEN EarliestSeenDate < ' . $this->quote($newDate) . ' THEN EarliestSeenDate ELSE ' . $this->quote($newDate) . ' END
                    WHERE UserCreditCardID = ' . $row['UserCreditCardID'] . '; ';
                $batchSize++;

                if ($batchSize >= 100) {
                    $this->connection->executeStatement($sql);
                    $sql = '';
                    $batchSize = 0;
                }
            }
        }

        if ($batchSize > 0) {
            $this->connection->executeStatement($sql);
        }
    }

    private function updateEarliestSeenDateFromClickHouse2(): void
    {
        $this->output->writeln(__FUNCTION__);

        $userCreditCards = $this->connection->fetchAllAssociative('
            SELECT UserCreditCardID, UserID, CreditCardID, EarliestSeenDate
            FROM UserCreditCard
            WHERE ' . (empty($this->userIds) ? '1' : 'UserID IN (' . implode(',', $this->userIds) . ')')
        );
        $db = $this->clickHouseService->getActiveDbName();

        $chunks = array_chunk($userCreditCards, 10000);
        $sql = '';
        $batchSize = 0;

        foreach ($chunks as $userCreditCards) {
            $userIds = array_column($userCreditCards, 'UserID');
            $list = $this->clickHouse->executeQuery("
                SELECT a.UserID, ah.AccountID, MIN(ah.PostingDate) AS minPostingDate, sa.CreditCardID 
                FROM {$db}.AccountHistory ah
                JOIN {$db}.Account a ON a.AccountID = ah.AccountID
                JOIN {$db}.SubAccount sa ON sa.SubAccountID = ah.SubAccountID
                WHERE a.UserID IN (" . implode(',', $userIds) . ") AND sa.CreditCardID IS NOT NULL
                GROUP BY a.UserID, ah.AccountID, sa.CreditCardID"
            )->fetchAllAssociative();

            foreach ($list as $row) {
                $sql .= '
                    UPDATE UserCreditCard
                    SET EarliestSeenDate = ' . $this->quote($row['minPostingDate']) . '
                    WHERE
                            UserID = ' . $row['a.UserID'] . '
                        AND CreditCardID = ' . $row['sa.CreditCardID'] . '
                        AND EarliestSeenDate > ' . $this->quote($row['minPostingDate']) . ';
                ';
                $batchSize++;

                if ($batchSize >= 100) {
                    $this->connection->executeStatement($sql);
                    $sql = '';
                    $batchSize = 0;
                }
            }
        }

        if ($batchSize > 0) {
            $this->connection->executeStatement($sql);
        }
    }

    private function removeFilteredEntries(): void
    {
        $this->output->writeln(__FUNCTION__);
        $closedCards = $this->connection->fetchAll('
            SELECT *
            FROM UserCreditCard
            WHERE
                ' . (empty($this->userIds) ? '' : 'UserID IN (' . implode(',', $this->userIds) . ') AND ') . '
                IsClosed = 1
        ');

        $closedCards_chunks = array_chunk($closedCards, self::ACCOUNTHISTORY_PER_REQUEST);

        foreach ($closedCards_chunks as $closedCards) {
            $userIds = array_column($closedCards, 'UserID');
            // $subAccountCards = $this->creditCardQueries->fetchSubAccountCards($this->creditCardIds, $userIds);
            $detectedCards = $this->creditCardQueries->fetchDetectedCards($this->creditCardIds, $userIds);
            $filtered = $this->userCreditCardsUtil->filterAccounts($detectedCards);

            $_detectedCards = array_combine(array_column($detectedCards, 'AccountID'), $detectedCards);
            $_filtered = array_combine(array_column($filtered, 'AccountID'), $filtered);

            $needRemove = [];

            foreach ($_detectedCards as $accountId => $row) {
                if (!array_key_exists($accountId, $_filtered)) {
                    $needRemove[] = $row;
                }
            }

            $removed = 0;

            foreach ($needRemove as $row) {
                // $this->output->writeln('UserCreditCard remove Filtered entries', $row);
                $removed += $this->connection->delete('UserCreditCard', [
                    'UserID' => $row['UserID'],
                    'CreditCardID' => $row['CreditCardID'],
                    'AccountID' => $row['AccountID'],
                ]);
            }

            $this->output->writeln('Removed: plan - ' . count($needRemove) . ', actual - ' . $removed);
        }
    }

    private function postProcess(): void
    {
        $this->output->writeln(__FUNCTION__);
        $qsEarliestDate = $this->connection->executeStatement('
            UPDATE UserCreditCard ucc
            SET EarliestSeenDate = LastSeenDate
            WHERE
                    ' . self::FIELD_DETECTEDVIAQS . ' = 1
                AND EarliestSeenDate IS NULL
                AND LastSeenDate IS NOT NULL
                ' . (empty($this->userIds) ? '' : 'AND ucc.UserID IN (' . implode(',', $this->userIds) . ')') . '
        ');
        $this->output->writeln('Affected QuinStreet cards EarliestSeenDate : ' . $qsEarliestDate);

        $affected = $this->connection->executeStatement('
            UPDATE UserCreditCard ucc
            JOIN Account a ON (a.AccountID = ucc.AccountID)
            SET IsClosed = 1
            WHERE
                    ucc.' . self::FIELD_DETECTEDVIACOBRAND . ' = 1
                AND ucc.' . self::FIELD_DETECTEDVIABANK . ' = 0
                AND ucc.IsClosed = 0
                AND DATE_SUB(NOW(), INTERVAL 1 YEAR) > ucc.LastSeenDate
                AND a.SuccessCheckDate > ucc.LastSeenDate
                ' . (empty($this->userIds) ? '' : 'AND ucc.UserID IN (' . implode(',', $this->userIds) . ')') . '
        ');
        $this->output->writeln('Close co-branded cards found in transactions older than 1 year : ' . $affected);

        $affected = $this->connection->executeStatement('
            UPDATE UserCreditCard ucc
            SET IsClosed = 1
            WHERE
                    ucc.' . self::FIELD_DETECTEDVIAQS . ' = 1
                AND ucc.' . self::FIELD_DETECTEDVIABANK . ' = 0
                AND ucc.' . self::FIELD_DETECTEDVIACOBRAND . ' = 0
                AND ucc.' . self::FIELD_DETECTEDVIAEMAIL . ' = 0
                AND DATE_SUB(NOW(), INTERVAL 1 YEAR) > ucc.LastSeenDate
                AND ucc.IsClosed = 0
                ' . (empty($this->userIds) ? '' : 'AND ucc.UserID IN (' . implode(',', $this->userIds) . ')') . '
        ');
        $this->output->writeln('Closed cards found only through QS older than 1 year : ' . $affected);
    }

    private function quote(?string $value): ?string
    {
        return $this->connection->quote($value);
    }
}
