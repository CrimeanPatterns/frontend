<?php

namespace AwardWallet\MainBundle\Command\Fix;

use AwardWallet\MainBundle\FrameworkExtension\Command;
use Doctrine\DBAL\Connection;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

use function AwardWallet\MainBundle\Globals\Utils\stmt\stmtAssoc;

class FixAccountHistoryCommand extends Command
{
    public static $defaultName = 'aw:fix-account-history';

    private Connection $replicaUnbufferedConnection;

    private Connection $connection;

    public function __construct(Connection $replicaUnbufferedConnection, Connection $connection)
    {
        parent::__construct();
        $this->replicaUnbufferedConnection = $replicaUnbufferedConnection;
        $this->connection = $connection;
    }

    protected function configure()
    {
        $this
            ->addOption('limit', null, InputOption::VALUE_REQUIRED)
            ->addOption('where', null, InputOption::VALUE_REQUIRED, 'sql where, available aliases Account as a, AccountHistory as h')
            ->addOption('print', null, InputOption::VALUE_NONE)
            ->addOption('apply', null, InputOption::VALUE_NONE, 'apply fixes, otherwise dry run')
            ->addOption('backup', null, InputOption::VALUE_REQUIRED, 'backup changed records to this file')
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $output->writeln("searching wrong history..");
        $q = $this->replicaUnbufferedConnection->executeQuery("select
            a.UserID, 
            h.*
        from
            AccountHistory h
            join Account a on a.AccountID = h.AccountID
        where
            h.SubAccountID is not null
            and " . $input->getOption('where')
        . ($input->getOption('limit') ? " limit " . $input->getOption('limit') : ''));

        $chain = stmtAssoc($q)
            ->onNthMillisAndLast(30000, function (int $millisFromStart, int $iteration, $currentValue, $currentKey, bool $isLast) use ($output) {
                $output->writeln("processed $iteration records..");
            })
            ->map(function (array $row) {
                $row['InfoDecoded'] = unserialize($row['Info'], ['allowed_classes' => false]);

                return $row;
            })
        ;

        if ($input->getOption('print')) {
            $chain = $chain->onEach(function (array $row) use ($output) {
                $output->writeln("{$row['UUID']} {$row['Description']}, Miles: {$row['Miles']}, Amount: {$row['Amount']}, Cat: {$row['Category']}, AccID: {$row['AccountID']}, UserID: {$row['UserID']}, " . ImplodeAssoc(": ", ", ", $row['InfoDecoded']));
            });
        }

        $backup = $input->getOption('backup');

        if ($backup) {
            $output->writeln("backing up to {$backup}");
            $file = fopen($backup, "wb");

            if ($file === false) {
                throw new \Exception("failed to open file $backup");
            }

            $chain = $chain
                ->onEach(function (array $row) use ($file) {
                    if (fwrite($file, json_encode(array_diff_key($row, ["InfoDecoded" => false])) . "\n") === false) {
                        throw new \Exception("failed to write");
                    }
                })
                ->finally(function () use ($file, $output) {
                    if (fclose($file) === false) {
                        throw new \Exception("failed to close file");
                    }

                    $output->writeln("backup saved");
                });
        }

        $fixed = 0;

        if ($input->getOption('apply')) {
            $updateQuery = $this->connection->prepare("update AccountHistory set Miles = null where UUID = ?");
            $chain = $chain
                ->onEach(function (array $row) use ($updateQuery, &$fixed) {
                    $updateQuery->executeStatement([$row['UUID']]);
                    $fixed++;
                });
        }

        $total = $chain->count();

        $output->writeln("done, processed $total records, fixed: $fixed");

        return 0;
    }
}
