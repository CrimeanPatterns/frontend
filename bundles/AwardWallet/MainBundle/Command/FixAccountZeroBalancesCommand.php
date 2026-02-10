<?php

namespace AwardWallet\MainBundle\Command;

use AwardWallet\MainBundle\FrameworkExtension\Command;
use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Result;
use Doctrine\DBAL\Statement;
use Psr\Log\LoggerInterface;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

use function AwardWallet\MainBundle\Globals\Utils\iterFluent\it;

class FixAccountZeroBalancesCommand extends Command
{
    protected static $defaultName = 'aw:fix:account-zero-balances';

    private LoggerInterface $logger;

    private Connection $conn;

    private Connection $unbufConnection;

    private Statement $updateAccountQuery;

    private Statement $countAccountBalancesQuery;

    private Statement $accountBalancesDescQuery;

    private Statement $accountBalancesAscQuery;

    private Statement $deleteQuery;

    public function __construct(LoggerInterface $logger, Connection $unbufConnection, Connection $connection)
    {
        parent::__construct();

        $this->logger = $logger;
        $this->unbufConnection = $unbufConnection;
        $this->conn = $connection;
        $this->conn->beginTransaction();

        $this->updateAccountQuery = $this->conn->prepare('UPDATE Account SET LastChangeDate = ?, ChangeCount = ?, LastBalance = ? WHERE AccountID = ?');
        $this->countAccountBalancesQuery = $this->conn->prepare('SELECT COUNT(*) FROM AccountBalance WHERE AccountID = ? AND SubAccountID IS NULL');
        $this->accountBalancesDescQuery = $this->conn->prepare('SELECT * FROM AccountBalance WHERE AccountID = ? AND SubAccountID IS NULL ORDER BY UpdateDate DESC');
        $this->accountBalancesAscQuery = $this->conn->prepare('SELECT * FROM AccountBalance WHERE AccountID = ? AND SubAccountID IS NULL ORDER BY UpdateDate ASC');
        $this->deleteQuery = $this->conn->prepare('DELETE FROM AccountBalance WHERE AccountBalanceID = ?');
    }

    protected function configure(): void
    {
        $this
            ->setDescription('Deleting zero AccountBalance records for the selected period, recalculating Account.ChangeCount, Account.LastBalance, Account.LastChangeDate')
            ->addOption('start-date', null, InputOption::VALUE_REQUIRED, 'Start date', '2023-06-30')
            ->addOption('end-date', null, InputOption::VALUE_REQUIRED, 'End date', '2023-06-30')
            ->addOption('apply', null, InputOption::VALUE_NONE, 'apply fixes, otherwise dry run')
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $apply = !empty($input->getOption('apply'));

        $d1 = new \DateTime($input->getOption('start-date'));
        $d2 = (new \DateTime($input->getOption('end-date')))->modify("+1 day");

        if ($d1 >= $d2) {
            $this->logger->error('wrong period');

            return 0;
        }

        $d1 = $d1->format('Y-m-d');
        $d2 = $d2->format('Y-m-d');

        $this->logger->info(sprintf('fix account zero balance started for period [%s : %s)', $d1, $d2));

        $q = $this->unbufConnection->executeQuery(/** @lang MySQL */ "
            SELECT ab.AccountBalanceID, ab.AccountID, a.Balance, a.SuccessCheckDate FROM AccountBalance ab
            LEFT JOIN Account a ON a.AccountID = ab.AccountID
            WHERE ab.UpdateDate >= ? AND ab.UpdateDate < ? AND (ab.Balance < -1 OR ab.Balance = 0)
            AND EXISTS(SELECT 1 FROM AccountBalance b WHERE b.Balance > 0 AND b.UpdateDate >= ? AND b.AccountID = ab.AccountID)
            AND a.SuccessCheckDate >= ?
        ", [$d1, $d2, $d2, $d2], []);

        $accountIds = [];
        $accountBalanceIds = [];
        $accountIdList = [];

        while ($row = $q->fetchAssociative()) {
            $accountIds[] = $row['AccountID'];
            $accountBalanceIds[] = $row['AccountBalanceID'];
            $accountIdList[$row['AccountID']][] = $row['AccountBalanceID'];
        }
        $accountIds = array_unique($accountIds);

        // del zeroes
        $this->conn->executeStatement(/** @lang MySQL */ "
            DELETE FROM AccountBalance WHERE AccountBalanceID IN (?)
        ", [$accountBalanceIds], [Connection::PARAM_INT_ARRAY]);

        $processedAccounts = 0;

        $q = $this->unbufConnection->executeQuery(/** @lang MySQL */ "
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
                    $accountIdList[$account['AccountID']][] = $accBalance['AccountBalanceID'];
                    $this->deleteAccountBalance($accBalance['AccountBalanceID']);
                }
            }

            $accountChangeCount = (int) $account['ChangeCount'];
            $numberAccountBalanceChanges = $this->numberAccountBalanceChanges($account['AccountID']);

            $this->logger->info(
                sprintf(
                    "%scorrect AccountID %d with balance: %s, last balance: %s, change count: %d, account balances changes: %d, last change date: %s",
                    !$apply ? 'will ' : '',
                    $account['AccountID'],
                    $account['Balance'] ?? 'null',
                    $account['LastBalance'] ?? 'null',
                    $accountChangeCount,
                    $numberAccountBalanceChanges,
                    $account['LastChangeDate'] ?? 'null'
                )
            );
            $this->logger->info(
                sprintf(
                    "\t%sdelete accountBalance ids: %s",
                    !$apply ? 'will ' : '',
                    json_encode($accountIdList[$account['AccountID']])
                )
            );

            if ($numberAccountBalanceChanges === 0) {
                $this->updateAccount($account['AccountID'], null, 0, null, $apply);
            } else {
                $history = $this->getAccountBalancesDesc($account['AccountID']);
                $prev = $history->fetchAssociative();

                if ($prev !== false && $account['Balance'] == $prev['Balance']) {
                    $prev2 = $history->fetchAssociative();
                    $this->updateAccount($account['AccountID'], $prev['UpdateDate'], $numberAccountBalanceChanges,
                        $prev2 ? $prev2['Balance'] : null, $apply);
                }
            }

            $processedAccounts++;
        }

        $this->logger->info(sprintf('processed %d accounts', $processedAccounts));

        if ($apply) {
            $this->conn->commit();
        } else {
            $this->conn->rollBack();
        }
        $output->writeln('done.');

        return 0;
    }

    private function updateAccount(int $accountId, ?string $lastChangeDate, int $changeCount, ?float $lastBalance, bool $apply)
    {
        $this->logger->info(sprintf(
            "\t%supdate AccountId %d: LastChangeDate: %s, ChangeCount: %s, LastBalance: %s",
            !$apply ? 'will ' : '',
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

    private function getAccountBalancesDesc(int $accountId): Result
    {
        return $this->accountBalancesDescQuery->executeQuery([$accountId]);
    }

    private function getAccountBalancesAsc(int $accountId): Result
    {
        return $this->accountBalancesAscQuery->executeQuery([$accountId]);
    }

    private function deleteAccountBalance(int $id)
    {
        $this->deleteQuery->executeStatement([$id]);
    }
}
