<?php

namespace AwardWallet\MainBundle\Command;

use AwardWallet\MainBundle\Service\PasswordLeakChecker;
use AwardWallet\MainBundle\Service\ProgressLogger;
use Doctrine\DBAL\Connection;
use Doctrine\DBAL\FetchMode;
use Psr\Log\LoggerInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class CheckPwnedAccountsCommand extends Command
{
    private const PARAM_NAME = 'CheckPwnedUpToDate';
    /**
     * @var Connection
     */
    private $mainConnection;
    /**
     * @var LoggerInterface
     */
    private $logger;
    /**
     * @var Connection
     */
    private $unbufConnection;
    /**
     * @var PasswordLeakChecker
     */
    private $leakChecker;

    public function __construct(Connection $mainConnection, Connection $unbufConnection, PasswordLeakChecker $leakChecker, LoggerInterface $logger)
    {
        parent::__construct();
        $this->mainConnection = $mainConnection;
        $this->logger = $logger;
        $this->unbufConnection = $unbufConnection;
        $this->leakChecker = $leakChecker;
    }

    public function configure()
    {
        $this->setDefinition([
            new InputOption('reset', null, InputOption::VALUE_NONE, 'start from scrarch'),
        ]);
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $output->writeln("checking pwned accounts");

        $checkedUpToDate = $this->mainConnection->executeQuery("select Val from Param where Name = ?", [self::PARAM_NAME])->fetchColumn();

        if ($checkedUpToDate === false) {
            $checkedUpToDate = '2000-01-01';
        }
        $output->writeln("searching passwords changed from $checkedUpToDate");

        $q = $this->unbufConnection->executeQuery("SELECT 
            AccountID,
            UserID, 
            Login, 
            Pass,
            PassChangeDate,
            PwnedTimes 
        FROM 
            Account 
        WHERE 
            Pass <> '' AND Pass IS NOT NULL
            AND ProviderID not in (12) /* IHG */
            AND PassChangeDate >= ?
        ORDER BY
            PassChangeDate
        ", [$checkedUpToDate]);

        $count = 0;
        $leaks = 0;
        $progress = new ProgressLogger($this->logger, 100, 30);
        $checkpointTime = time();
        $lastPasschangeDate = null;

        while ($row = $q->fetch(FetchMode::ASSOCIATIVE)) {
            $progress->showProgress("checking passwords", $count);
            $password = DecryptPassword($row['Pass']);
            $leakCount = $this->leakChecker->checkPassword($password);

            if ($leakCount !== false) {
                $this->logger->info("leaked password", ["AccountID" => $row['AccountID'], "UserID" => $row['UserID'], "LeakCount" => $leakCount]);
                $this->mainConnection->executeUpdate("update Account set PwnedTimes = ? where AccountID = ?", [$leakCount, $row['AccountID']]);
                $leaks++;
            }

            if ($leakCount === false && !empty($row['PwnedTimes'])) {
                $this->mainConnection->executeUpdate("update Account set PwnedTimes = null where AccountID = ?", [$row['AccountID']]);
            }

            $time = time();

            if (($time - $checkpointTime) >= 30) {
                $this->saveCheckPoint($row['PassChangeDate']);
                $checkpointTime = $time;
            }
            $lastPasschangeDate = $row['PassChangeDate'];

            $count++;
        }

        if ($lastPasschangeDate !== null) {
            $this->saveCheckPoint($lastPasschangeDate);
        }

        $output->writeln("done, checked $count accounts, leaks: $leaks");

        return 0;
    }

    private function saveCheckPoint(string $date)
    {
        $this->logger->info("saving checkpoint {$date}");
        $this->mainConnection->executeUpdate("insert into Param(Name, Val) values (?, ?) on duplicate key update Val = values(Val)", [self::PARAM_NAME, $date]);
    }
}
