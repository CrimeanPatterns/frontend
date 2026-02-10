<?php

namespace AwardWallet\MainBundle\Service;

use AwardWallet\MainBundle\Event\AccountChangedEvent;
use AwardWallet\MainBundle\Event\UserPlusChangedEvent;
use Doctrine\DBAL\Connection;
use Psr\Log\LoggerInterface;

class BackgroundCheckUpdater
{
    public const YES = 'yes';
    public const NO = 'no';
    public const MAILBOX = 'mailbox';

    public const CHECK_STATUSES = [
        ACCOUNT_CHECKED,
        ACCOUNT_WARNING,
        ACCOUNT_PROVIDER_ERROR,
        ACCOUNT_ENGINE_ERROR,
        ACCOUNT_PROVIDER_DISABLED,
        ACCOUNT_UNCHECKED,
        ACCOUNT_INVALID_PASSWORD,
        ACCOUNT_QUESTION,
    ];

    private Connection $readConnection;

    private Connection $writeConnection;

    private LoggerInterface $logger;

    public function __construct(
        Connection $replicaUnbufferedConnection,
        Connection $writeConnection,
        LoggerInterface $logger
    ) {
        $this->readConnection = $replicaUnbufferedConnection;
        $this->writeConnection = $writeConnection;
        $this->logger = $logger;
    }

    public static function calcBackgroundCheck(int $state, bool $canCheck): string
    {
        if ($state === PROVIDER_CHECKING_WITH_MAILBOX && $canCheck) {
            return self::MAILBOX;
        }

        if (
            ($state >= PROVIDER_ENABLED || $state === PROVIDER_TEST)
            && $state !== PROVIDER_CHECKING_EXTENSION_ONLY
            && $state !== PROVIDER_CHECKING_OFF
            && $state !== PROVIDER_FIXING
            && $canCheck
        ) {
            return self::YES;
        }

        return self::NO;
    }

    /**
     * @return int affected accounts
     */
    public function updateProvider(int $providerId): int
    {
        $providerData = $this->readConnection
            ->executeQuery('SELECT State, CanCheck, PasswordRequired FROM Provider WHERE ProviderID = ?', [$providerId])
            ->fetchAssociative();

        if ($providerData === false) {
            throw new \InvalidArgumentException(sprintf('Wrong provider id %d', $providerId));
        }

        $state = (int) $providerData['State'];

        if ($state === PROVIDER_CHECKING_WITH_MAILBOX) {
            $affected = $this->updateAccountsWithProviderStateMailbox("AND a.ProviderID = $providerId", true);

            if ($affected > 0) {
                $this->logger->info('updated BackgroundCheck', ['ProviderID' => $providerId, 'Affected' => $affected]);
            }

            return $affected;
        }

        $passwordRequired = (bool) $providerData['PasswordRequired'];
        $canCheck = (bool) $providerData['CanCheck'];
        $canCheckCalc = static::calcBackgroundCheck($state, $canCheck);

        [$backgroundCheckSql, $joins] = $this->getStateSqlParams($passwordRequired, $canCheckCalc);

        $sql = "SELECT a.AccountID FROM Account a $joins WHERE a.ProviderID = ? and a.BackgroundCheck <> $backgroundCheckSql";
        $accounts = $this->readConnection->executeQuery($sql, [$providerId]);
        $affected = 0;
        $packet = [];

        while ($account = $accounts->fetchOne()) {
            $packet[] = $account;

            if (count($packet) > 100) {
                $affected += $this->savePacket($providerId, $packet, $joins, $backgroundCheckSql);
                $packet = [];
            }
        }

        if (!empty($packet)) {
            $affected += $this->savePacket($providerId, $packet, $joins, $backgroundCheckSql);
        }

        return $affected;
    }

    public function updateUser(int $userId)
    {
        $affected = $this->updateAccountsWithProviderStateMailbox("AND a.UserID = $userId");

        if ($affected > 0) {
            $this->logger->info('updated user BackgroundCheck (PROVIDER_CHECKING_WITH_MAILBOX)', ['UserID' => $userId, 'Affected' => $affected]);
        }

        $affected = $this->writeConnection->executeStatement("
            UPDATE 
                Account a
                JOIN Provider p ON p.ProviderID = a.ProviderID
                JOIN Usr u ON a.UserID = u.UserID
            SET 
                a.BackgroundCheck = " . $this->getStateSql('u.AccountLevel = ' . ACCOUNT_LEVEL_AWPLUS) . "
            WHERE
                p.State <> ?
                AND a.UserID = ?",
            [PROVIDER_CHECKING_WITH_MAILBOX, $userId],
            [\PDO::PARAM_INT, \PDO::PARAM_INT]
        );

        if ($affected > 0) {
            $this->logger->info('updated user BackgroundCheck', ['UserID' => $userId, 'Affected' => $affected]);
        }
    }

    public function onPlusChanged(UserPlusChangedEvent $event)
    {
        $this->updateUser((int) $event->getUserId());
    }

    public function onAccountChanged(AccountChangedEvent $event)
    {
        $accountId = (int) $event->getAccountId();
        $state = $this->writeConnection
            ->executeQuery('
                SELECT 
                    p.State 
                FROM 
                    Account a
                    JOIN Provider p ON p.ProviderID = a.ProviderID 
                WHERE a.AccountID = ?
            ', [$accountId])
            ->fetchOne();

        if ($state === false) {
            $this->logger->info('account not found BackgroundCheck', ['AccountID' => $accountId]);

            return;
        }

        $state = (int) $state;

        if ($state === PROVIDER_CHECKING_WITH_MAILBOX) {
            $affected = $this->updateAccountsWithProviderStateMailbox("AND a.AccountID = $accountId");

            if ($affected > 0) {
                $this->logger->info('updated account BackgroundCheck', ['AccountID' => $accountId]);
            }

            return;
        }

        $affected = $this->writeConnection->executeStatement("
            UPDATE
                Account a
                JOIN Provider p ON p.ProviderID = a.ProviderID
                JOIN Usr u ON a.UserID = u.UserID
            SET
                a.BackgroundCheck = " . $this->getStateSql('u.AccountLevel = ' . ACCOUNT_LEVEL_AWPLUS) . "
            WHERE
                a.AccountID = ?
        ", [$accountId]);

        if ($affected > 0) {
            $this->logger->info('updated account BackgroundCheck', ['AccountID' => $accountId]);
        }
    }

    /**
     * @return int affected accounts
     */
    private function updateAccountsWithProviderStateMailbox(string $filter = '', $useReadReplica = false): int
    {
        $affected = 0;
        $connection = $useReadReplica ? $this->readConnection : $this->writeConnection;
        $result = $connection->executeQuery("
            SELECT
                a.UserID,
                a.UserAgentID,
                u.ValidMailboxesCount
            FROM
                Account a
                JOIN Provider p ON p.ProviderID = a.ProviderID
                JOIN Usr u on a.UserID = u.UserID
            WHERE
                p.State = ?
                $filter
            GROUP BY a.UserID, a.UserAgentID
        ", [PROVIDER_CHECKING_WITH_MAILBOX]);

        while ($owner = $result->fetchAssociative()) {
            $count = $owner['ValidMailboxesCount'];

            $affected += $this->writeConnection->executeStatement("
                UPDATE
                    Account a
                    JOIN Provider p ON p.ProviderID = a.ProviderID
                SET
                    a.BackgroundCheck = " . $this->getStateSql("$count > 0") . "
                WHERE
                    p.State = ?
                    AND a.UserID = " . $owner['UserID'] . "
                    AND a.UserAgentID " . (empty($ua) ? 'IS NULL' : '= ' . $ua) . "
                    $filter
            ", [PROVIDER_CHECKING_WITH_MAILBOX]);
        }

        return $affected;
    }

    private function getStateSqlParams(bool $passwordRequired, string $canCheck): array
    {
        $yesSql = $this->getYesSql($passwordRequired);

        $canCheckToSql = [
            self::YES => 'CASE WHEN u.AccountLevel = ' . ACCOUNT_LEVEL_AWPLUS . ' THEN ' . $yesSql . ' ELSE 0 END',
            self::NO => '0',
        ];

        if (!isset($canCheckToSql[$canCheck])) {
            throw new \Exception("Invalid new state: $canCheck");
        }

        $sql = $canCheckToSql[$canCheck];
        $joins = ' join Usr u on a.UserID = u.UserID';

        return [$sql, $joins];
    }

    private function getYesSql(bool $passwordRequired): string
    {
        $yesConditions = [
            'a.Disabled = 0',
            'a.ErrorCode IN (' . implode(", ", self::CHECK_STATUSES) . ')',
            'a.State >= ' . ACCOUNT_UNCHECKED,
        ];

        if ($passwordRequired) {
            $yesConditions[] = $this->getPasswordRequiredSql();
        }

        return 'CASE WHEN ' . implode(' AND ', $yesConditions) . ' THEN 1 ELSE 0 END';
    }

    private function getPasswordRequiredSql(): string
    {
        return '
        (
            (a.SavePassword = ' . SAVE_PASSWORD_DATABASE . ' AND a.Pass <> "" AND a.Pass IS NOT NULL)
            OR (a.ProviderID = ' . AA_PROVIDER_ID . ' AND a.ErrorCode = ' . ACCOUNT_CHECKED . ' AND a.SuccessCheckDate > a.PassChangeDate AND a.UpdateDate > "2014-04-16")
            OR (a.ProviderID IN (104, 75) /* capitalcards, bankofamerica */ AND a.AuthInfo IS NOT NULL)
            OR (a.ProviderID = 636 /* testprovider */ ) 
        )';
    }

    private function savePacket($providerId, array $accounts, $joins, $newStateSql): int
    {
        $affected = $this->writeConnection->executeStatement(
            "UPDATE 
                Account a
                $joins
            SET
                a.BackgroundCheck = " . $newStateSql . "
            WHERE
                a.AccountID in (" . implode(', ', $accounts) . ")"
        );

        if ($affected > 0) {
            $this->logger->info("updated BackgroundCheck", ['ProviderID' => $providerId, 'Accounts' => count($accounts), 'Affected' => $affected]);
        }

        return $affected;
    }

    private function getStateSql(?string $trigger = null): string
    {
        return "
            IF(
                " . ($trigger ?? '1 = 1') . "
                AND (p.State >= " . PROVIDER_ENABLED . " || p.State = " . PROVIDER_TEST . ")
                AND p.State <> " . PROVIDER_CHECKING_EXTENSION_ONLY . "
                AND p.State <> " . PROVIDER_CHECKING_OFF . "
                AND p.State <> " . PROVIDER_FIXING . "
                AND a.Disabled = 0
                AND a.DisableBackgroundUpdating = 0
                AND a.ErrorCode IN (" . implode(", ", self::CHECK_STATUSES) . ")
                AND a.State >= " . ACCOUNT_UNCHECKED . "
                AND (
                    p.PasswordRequired = 0 
                    OR (a.SavePassword = " . SAVE_PASSWORD_DATABASE . " AND a.Pass <> '' AND a.Pass IS NOT NULL)
                    OR (a.ProviderID = " . AA_PROVIDER_ID . " AND a.ErrorCode = " . ACCOUNT_CHECKED . " AND a.SuccessCheckDate > a.PassChangeDate AND a.UpdateDate > '2014-04-16')
                    OR (a.ProviderID IN (104, 75) /* capitalcards, bankofamerica */ AND a.AuthInfo IS NOT NULL)
                    OR (a.ProviderID = 636 /* testprovider */ )
                ),
                1,
                0
            )
        ";
    }
}
