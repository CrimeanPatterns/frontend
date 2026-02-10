<?php

namespace AwardWallet\MainBundle\Command\Fix;

use AwardWallet\Common\Doctrine\BatchUpdater;
use AwardWallet\MainBundle\Service\ClickhouseFactory;
use Doctrine\DBAL\Connection;
use Doctrine\DBAL\FetchMode;
use Psr\Log\LoggerInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Helper\Table;
use Symfony\Component\Console\Helper\TableCell;
use Symfony\Component\Console\Helper\TableSeparator;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

use function AwardWallet\MainBundle\Globals\Utils\iterFluent\it;

class FixChaseRefundsCommand extends Command
{
    public static $defaultName = 'aw:fix:chase-refunds';
    /**
     * @var Connection
     */
    private $clickhouse;
    /**
     * @var Connection
     */
    private $connection;
    /**
     * @var LoggerInterface
     */
    private $logger;
    /**
     * @var ClickhouseFactory
     */
    private $clickhouseFactory;

    public function __construct(Connection $clickhouse, Connection $connection, LoggerInterface $logger, ClickhouseFactory $clickhouseFactory)
    {
        parent::__construct();

        $this->clickhouse = $clickhouse;
        $this->connection = $connection;
        $this->logger = $logger;
        $this->clickhouseFactory = $clickhouseFactory;
    }

    public function configure()
    {
        $this
            ->addOption('limit', null, InputOption::VALUE_REQUIRED, 'limit')
            ->addOption('apply', null, InputOption::VALUE_NONE, 'apply fixes, otherwise dry run')
            ->addOption('print', null, InputOption::VALUE_NONE, 'show found records')
            ->addOption('check', null, InputOption::VALUE_NONE, 'check that records are actually refunds')
            ->addOption('where', null, InputOption::VALUE_REQUIRED, 'where filter', 'a.ProviderID = 87 and h.Amount > 0 and h.Miles < 0')
            ->addOption('orderBy', null, InputOption::VALUE_REQUIRED, 'order by, for example "h.PostingDate DESC"')
            ->addOption('backup', null, InputOption::VALUE_REQUIRED, 'backup modified UUIDs list to file')
        ;
    }

    public function execute(InputInterface $input, OutputInterface $output): int
    {
        $output->writeln("searching for positive refunds");
        $where = $input->getOption('where');
        $sql = "select h.UUID, h.PostingDate, h.AccountID, h.SubAccountID, h.Amount, h.Miles from AccountHistory h join Account a on h.AccountID = a.AccountID where $where";

        if ($orderBy = $input->getOption('orderBy')) {
            $sql .= " order by " . (int) $orderBy;
        }

        if ($limit = $input->getOption('limit')) {
            $sql .= " limit " . (int) $limit;
        }

        $apply = $input->getOption('apply');
        $print = $input->getOption('print');
        $check = $input->getOption('check');
        $backup = $input->getOption('backup');

        $rows = [];
        $headers = ['UUID', 'PostingDate', 'AccountID', 'SubAccountID', 'Amount', 'Miles'];

        $q = $this->clickhouse->executeQuery($sql);

        $output->writeln("query opened, processing");
        $batcher = new BatchUpdater($this->connection);
        $total = 0;

        $chain = it($q)
            ->onNthMillis(10000, function ($time, $ticksCounter, $value, $key) use ($output) {
                $output->writeln("processed $ticksCounter records in " . ($time / 1000) . " seconds..");
            })
            ->onEach(function () use (&$total) {
                $total++;
            })
        ;

        if ($print || $check) {
            $chain = $chain
                ->chunk(50)
                ->flatMap([$this, "loadRowsInfo"])
            ;
            $headers = array_merge($headers, ['UserID', 'Refund']);
        }

        if ($print) {
            $chain = $chain
                ->onEach(function (array $row) use (&$rows, $limit, $headers, $output) {
                    if ($limit) {
                        $rows[] = $headers;
                        $rows[] = array_intersect_key($row, array_flip($headers));
                        $rows[] = [new TableCell($row['Description'], ['colspan' => count($headers)])];

                        $rows = array_merge($rows, $this->showInfo($row['Info'], count($headers)));

                        if ($row['PaymentInfo'] !== null) {
                            $rows[] = [new TableCell("Payment info", ['colspan' => count($headers)])];
                            $rows = array_merge($rows, $this->showInfo($row['PaymentInfo'], count($headers)));
                        }

                        $rows[] = new TableSeparator();
                    } else {
                        $output->writeln("{$row["UUID"]} {$row["PostingDate"]} {$row["Description"]} \${$row["Amount"]} {$row['Miles']}");
                    }

                    return $row;
                })
            ;
        }

        if ($check) {
            $chain = $chain
                ->filter(function (array $row) {
                    return $row['Refund'] !== 'No';
                })
            ;
        }

        if ($backup) {
            $output->writeln("backing up to {$backup}");
            $file = fopen($backup, "wb");

            if ($file === false) {
                throw new \Exception("failed to open file $backup");
            }

            $chain = $chain
                ->onEach(function (array $row) use ($file) {
                    if (fwrite($file, $row['UUID'] . "\n") === false) {
                        throw new \Exception("failed to write");
                    }
                })
                ->finally(function () use ($file, $output) {
                    if (fclose($file) === false) {
                        throw new \Exception("failed to close file");
                    }

                    $output->writeln("backup saved");
                })
            ;
        }

        if ($apply) {
            $chain = $chain
                ->map(function (array $row) {
                    return [$row['UUID']];
                })
                ->chunk(50)
                ->map(function (array $uuids) use ($batcher) {
                    $batcher->batchUpdate($uuids, "update AccountHistory set Amount = -Amount where Amount > 0 and UUID = ? limit 1", 0);

                    return count($uuids);
                })
            ;
        }

        $count = $chain->sum();

        if ($print && $limit) {
            $table = new Table($output);
            $table
                ->setRows($rows)
            ;
            $table->render();
        }

        $output->writeln("done, processed {$total} rows, found: {$count}");

        return 0;
    }

    public function loadRowsInfo(array $rows): array
    {
        $uuids = it($rows)->map(function (array $row) { return $row['UUID']; })->toArray();
        $q = $this->connection->executeQuery(
            "select 
                h.UUID, h.Description, h.Info, a.UserID, p.Info as PaymentInfo 
            from 
                AccountHistory h
                join Account a on a.AccountID = h.AccountID 
                left join AccountHistory p on p.AccountID = h.AccountID and p.SubAccountID = h.SubAccountID and p.Amount = h.Amount and p.Description = h.Description and p.Miles = -1 * h.Miles and p.PostingDate <= h.PostingDate and p.UUID <> h.UUID
            where 
                h.UUID in (?)",
            [$uuids],
            [Connection::PARAM_STR_ARRAY]
        );
        $extraRows = $q->fetchAll(FetchMode::ASSOCIATIVE);
        $extraRows = it($extraRows)->reindex(function (array $row) { return $row['UUID']; })->toArrayWithKeys();

        return
            it($rows)
                ->map(function (array $row) use ($extraRows) {
                    $extra = $extraRows[$row['UUID']] ?? ['Description' => '', 'Info' => null, 'UserID' => '', 'PaymentInfo' => null];
                    $row = array_merge($row, $extra);
                    $row['Refund'] = 'No';

                    if ($row['Info'] === null) {
                        $row['Info'] = [];
                    } else {
                        $row['Info'] = unserialize($extra['Info'], ['allowed_classes' => false]);
                    }

                    if ($row['PaymentInfo'] !== null) {
                        $row['PaymentInfo'] = unserialize($extra['PaymentInfo'], ['allowed_classes' => false]);
                    }

                    // Details like: "Return, Bonus earn" means refund on chase
                    if (stripos($info['Details'] ?? '', 'Return') !== false) {
                        $row['Refund'] = 'Yes, Return found';
                    }

                    if ($row['PaymentInfo'] !== null) {
                        $row['Refund'] = 'Yes, matched payment';
                    }

                    return $row;
                })
                ->toArray();
    }

    private function showInfo(array $info, int $colspan): array
    {
        $rows = [];

        foreach ($info as $key => $value) {
            $value = html_entity_decode($value);

            if (in_array(substr($value, 0, 1), ['[', '{'])) {
                $json = @json_decode($value, true);

                if ($json !== null) {
                    $value = json_encode($json, JSON_PRETTY_PRINT);
                }
            }

            $rows[] = [new TableCell("$key: $value", ['colspan' => $colspan])];
        }

        return $rows;
    }
}
