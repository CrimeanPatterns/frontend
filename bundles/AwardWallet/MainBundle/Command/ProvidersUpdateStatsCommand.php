<?php

namespace AwardWallet\MainBundle\Command;

use AwardWallet\MainBundle\Entity\Account;
use AwardWallet\MainBundle\FrameworkExtension\Command;
use Doctrine\DBAL\Connection;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class ProvidersUpdateStatsCommand extends Command
{
    protected static $defaultName = 'aw:providers:update-stats';

    private Connection $connection;

    public function __construct(
        Connection $connection
    ) {
        parent::__construct();
        $this->connection = $connection;
    }

    protected function configure()
    {
        $this
            ->setDescription('Update statistics of account checks')
            ->addOption('dry-run', null, InputOption::VALUE_NONE, 'Dry run, do not change anything')
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $period = -7;

        $db = $this->connection;
        $rows = $db->executeQuery('SELECT ProviderID, Name, Code FROM Provider WHERE CanCheck = 1 AND State >= ' . PROVIDER_ENABLED)->fetchAll(\PDO::FETCH_ASSOC);
        $checkedByFilter = 'CheckedBy IN (' . implode(', ', Account::CHECKED_BY_USER) . ')';
        $stmtSelectWithout = $db->prepare('
            SELECT AVG(LastDurationWithoutPlans) AS "avg" 
            FROM Account 
            WHERE 
                ProviderID = ? AND 
                UpdateDate >= adddate(now(), ' . $period . ') AND 
                ' . $checkedByFilter . ' AND
                SuccessCheckDate is not null and
                (
                    LastCheckItDate is null or
                    SuccessCheckDate > LastCheckItDate
                ) and
                LastDurationWithoutPlans IS NOT NULL AND 
                ErrorCode = ' . ACCOUNT_CHECKED
        );
        $stmtSelectWith = $db->prepare('
            SELECT AVG(LastDurationWithPlans) AS "avg" 
            FROM Account 
            WHERE 
                ProviderID = ? AND 
                UpdateDate >= adddate(now(), ' . $period . ') AND
                ' . $checkedByFilter . ' AND
                LastCheckItDate is not null and
                SuccessCheckDate is not null and
                LastCheckItDate = SuccessCheckDate AND
                LastDurationWithPlans IS NOT NULL AND 
                ErrorCode = ' . ACCOUNT_CHECKED
        );
        $stmtUpdate = $db->prepare('UPDATE Provider SET AvgDurationWithoutPlans = ?, AvgDurationWithPlans = ? WHERE ProviderID = ?');

        foreach ($rows as $row) {
            $stmtSelectWithout->execute([$row['ProviderID']]);
            $avgWithout = $stmtSelectWithout->fetchColumn(0);
            $stmtSelectWith->execute([$row['ProviderID']]);
            $avgWith = $stmtSelectWith->fetchColumn(0);

            if (!$input->getOption('dry-run')) {
                $stmtUpdate->execute([$avgWithout, $avgWith, $row['ProviderID']]);
            }

            $output->writeln($row['Code'] . ": with plans: " . ($avgWith !== null ? $avgWith : 'NULL') . ", without plans: " . ($avgWithout !== null ? $avgWithout : 'NULL'));
        }
        $output->writeln("Done, " . count($rows) . " providers updated");

        return 0;
    }
}
