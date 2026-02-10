<?php

namespace AwardWallet\MainBundle\Command\CreditCards;

use AwardWallet\Common\Doctrine\BatchUpdater;
use AwardWallet\MainBundle\Service\CreditCards\MerchantDisplayNameGenerator;
use Doctrine\DBAL\Connection;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

use function AwardWallet\MainBundle\Globals\Utils\stmt\stmtAssoc;

class MerchantDisplayNameCommand extends Command
{
    public static $defaultName = 'aw:credit-cards:merchant-display-name';
    /** @var Connection */
    private $connection;
    /** @var Connection */
    private $replicaConnection;

    public function __construct(
        Connection $connection,
        Connection $replicaUnbufferedConnection
    ) {
        parent::__construct();

        $this->connection = $connection;
        $this->replicaConnection = $replicaUnbufferedConnection;
    }

    protected function configure()
    {
        $this
            ->addOption('merchantId', null, InputOption::VALUE_REQUIRED, 'only this merchant id')
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $output->writeln("processing merchants to fill merchant names..");
        $batchUpdater = new BatchUpdater($this->connection);
        $updateSql = 'update Merchant set DisplayName = ? where MerchantID = ? and IsCustomDisplayName = 0';
        $currentMerchantId = null;
        $names = [];
        $updated = 0;
        $merchants = 0;

        $filter = "MerchantID is not null";
        $params = [];

        if ($merchantId = $input->getOption('merchantId')) {
            $filter = "MerchantID = :merchantId";
            $params["merchantId"] = $merchantId;
            $output->writeln("filtering by merchant id {$merchantId}");
        }

        stmtAssoc($this->replicaConnection->executeQuery("select MerchantID, Description from AccountHistory where $filter 
        order by MerchantID", $params))
            ->onNthMillisAndLast(30000, function (int $millisFromStart, int $iteration, $currentValue, $currentKey) use ($output, &$updated, &$merchants) {
                $output->writeln("processed " . number_format($iteration) . " rows in " . number_format($millisFromStart / 1000) . " seconds, " . number_format($merchants) . " merchants, updated " . number_format($updated) . " merchants");
            })
            ->flatMap(function (array $row) use (&$currentMerchantId, &$names, &$merchants) {
                $result = [];

                if ($currentMerchantId === null) {
                    $currentMerchantId = $row['MerchantID'];
                }

                if ($currentMerchantId !== $row['MerchantID']) {
                    $result[] = [$this->createMerchantName($names), $currentMerchantId];
                    $names = [];
                    $merchants++;
                    $currentMerchantId = $row['MerchantID'];
                }

                $description = $row['Description'];
                $names[$description] = ($names[$description] ?? 0) + 1;

                return $result;
            })
            ->chunk(50)
            ->apply(function (array $merchantsBatch) use ($batchUpdater, $updateSql, &$updated) {
                $updated += $batchUpdater->batchUpdate($merchantsBatch, $updateSql, 0);
            });

        if (count($names) > 0) {
            $batch = [[$this->createMerchantName($names), $currentMerchantId]];

            if ($merchantId && count($names)) {
                $output->writeln("name set to: " . json_encode($batch));
            }

            $updated += $batchUpdater->batchUpdate($batch, $updateSql, 0);
            $merchants++;
        }

        $output->writeln("done, processed " . number_format($merchants) . " merchants, updated " . number_format($updated) . " merchants");

        return 0;
    }

    private function createMerchantName(array $names): string
    {
        asort($names);

        return MerchantDisplayNameGenerator::create(array_key_last($names));
    }
}
