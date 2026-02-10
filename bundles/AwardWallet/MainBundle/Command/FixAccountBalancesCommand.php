<?php

namespace AwardWallet\MainBundle\Command;

use AwardWallet\MainBundle\FrameworkExtension\Command;
use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Result;
use Doctrine\DBAL\Statement;
use Psr\Log\LoggerInterface;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

use function AwardWallet\MainBundle\Globals\Utils\iterFluent\it;

class FixAccountBalancesCommand extends Command
{
    protected static $defaultName = 'aw:fix:account-balances';

    private LoggerInterface $logger;

    private Connection $conn;

    private Connection $unbufConnection;

    private Statement $deleteQuery;

    private Statement $updateAccountQuery;

    private Statement $countAccountBalancesQuery;

    private Statement $accountBalancesAscQuery;

    private Statement $accountBalancesDescQuery;

    public function __construct(LoggerInterface $logger, Connection $unbufConnection, Connection $connection)
    {
        parent::__construct();

        $this->logger = $logger;
        $this->unbufConnection = $unbufConnection;
        $this->conn = $connection;

        $this->deleteQuery = $this->conn->prepare('DELETE FROM AccountBalance WHERE AccountBalanceID = ?');
        $this->updateAccountQuery = $this->conn->prepare('UPDATE Account SET LastChangeDate = ?, ChangeCount = ?, LastBalance = ? WHERE AccountID = ?');
        $this->countAccountBalancesQuery = $this->conn->prepare('SELECT COUNT(*) FROM AccountBalance WHERE AccountID = ? AND SubAccountID IS NULL');
        $this->accountBalancesAscQuery = $this->conn->prepare('SELECT * FROM AccountBalance WHERE AccountID = ? AND SubAccountID IS NULL ORDER BY UpdateDate ASC');
        $this->accountBalancesDescQuery = $this->conn->prepare('SELECT * FROM AccountBalance WHERE AccountID = ? AND SubAccountID IS NULL ORDER BY UpdateDate DESC');
    }

    protected function configure(): void
    {
        $this
            ->setDescription('Deleting duplicate AccountBalance records, recalculating Account.ChangeCount, Account.LastBalance, Account.LastChangeDate');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $q = $this->unbufConnection->executeQuery("
            SELECT AccountBalanceID, AccountID FROM AccountBalance WHERE UpdateDate > NOW()
        ");

        $accountIds = [];
        $accountBalanceIds = [];

        while ($row = $q->fetchAssociative()) {
            $accountIds[] = $row['AccountID'];
            $accountBalanceIds[] = $row['AccountBalanceID'];
        }
        $accountIds = array_unique($accountIds);

        $this->conn->executeStatement("
            DELETE FROM AccountBalance WHERE AccountBalanceID IN (?)
        ", [$accountBalanceIds], [Connection::PARAM_INT_ARRAY]);

        $processedAccounts = 0;

        $q = $this->unbufConnection->executeQuery("
            SELECT
                AccountID,
                Balance,
                ChangeCount,
                LastBalance,
                LastChangeDate
            FROM
                Account
            WHERE
                AccountID IN (?)
        ", [$accountIds], [Connection::PARAM_INT_ARRAY]);

        while ($account = $q->fetchAssociative()) {
            $accountChangeCount = (int) $account['ChangeCount'];
            $numberAccountBalanceChanges = $this->numberAccountBalanceChanges($account['AccountID']);
            $wrongAccountFields = $accountChangeCount === 0
                && $numberAccountBalanceChanges === $accountChangeCount
                && (!empty($account['LastBalance']) || !empty($account['LastChangeDate']));

            if ($numberAccountBalanceChanges !== $accountChangeCount || $wrongAccountFields) {
                $this->logger->info(
                    sprintf(
                        'processing account #%d, balance: %s, last balance: %s, change count: %d, account balances changes: %d, last change date: %s',
                        $account['AccountID'],
                        $account['Balance'] ?? 'null',
                        $account['LastBalance'] ?? 'null',
                        $accountChangeCount,
                        $numberAccountBalanceChanges,
                        $account['LastChangeDate'] ?? 'null'
                    )
                );

                foreach (
                    it($this->getAccountBalancesAsc($account['AccountID'])->iterateAssociative())
                        ->groupAdjacentBy(function (array $a, array $b) {
                            return $a['Balance'] <=> $b['Balance'];
                        })->filter(function (array $group) {
                            return count($group) > 1;
                        }) as $group
                ) {
                    array_shift($group);

                    foreach ($group as $accBalance) {
                        $this->logger->info(
                            sprintf(
                                'deleting an extra AccountBalance row #%d, balance: %s',
                                $accBalance['AccountBalanceID'],
                                $accBalance['Balance'] ?? 'null'
                            )
                        );
                        $this->deleteAccountBalance($accBalance['AccountBalanceID']);
                    }
                }

                $numberAccountBalanceChanges = $this->numberAccountBalanceChanges($account['AccountID']);

                if ($numberAccountBalanceChanges === 0) {
                    $this->updateAccount($account['AccountID'], null, 0, null);
                } else {
                    $history = $this->getAccountBalancesDesc($account['AccountID']);
                    $prev = $history->fetchAssociative();

                    if ($prev !== false && $account['Balance'] == $prev['Balance']) {
                        $prev2 = $history->fetchAssociative();
                        $this->updateAccount($account['AccountID'], $prev['UpdateDate'], $numberAccountBalanceChanges, $prev2 ? $prev2['Balance'] : null);
                    }
                }

                $processedAccounts++;
            }
        }

        $this->logger->info(sprintf('processed %d accounts', $processedAccounts));
        $output->writeln('done.');

        return 0;
    }

    private function deleteAccountBalance(int $id)
    {
        $this->deleteQuery->executeStatement([$id]);
    }

    private function updateAccount(int $accountId, ?string $lastChangeDate, int $changeCount, ?float $lastBalance)
    {
        $this->logger->info(sprintf(
            'update account #%d: LastChangeDate: %s, ChangeCount: %s, LastBalance: %s',
            $accountId,
            $lastChangeDate ?? 'null',
            $changeCount,
            $lastBalance ?? 'null',
        ));

        $this->updateAccountQuery->executeStatement([$lastChangeDate, $changeCount, $lastBalance, $accountId]);
    }

    private function numberAccountBalanceChanges(int $accountId): int
    {
        return \max((int) $this->countAccountBalancesQuery->executeQuery([$accountId])->fetchOne() - 1, 0);
    }

    private function getAccountBalancesAsc(int $accountId): Result
    {
        return $this->accountBalancesAscQuery->executeQuery([$accountId]);
    }

    private function getAccountBalancesDesc(int $accountId): Result
    {
        return $this->accountBalancesDescQuery->executeQuery([$accountId]);
    }
}
