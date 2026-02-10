<?php

namespace AwardWallet\MainBundle\Command;

use AwardWallet\Common\Doctrine\BatchUpdater;
use AwardWallet\MainBundle\FrameworkExtension\Command;
use Doctrine\DBAL\Connection;
use Psr\Log\LoggerInterface;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

use function AwardWallet\MainBundle\Globals\Utils\stmt\stmtAssoc;

class CleanRAFlightCommand extends Command
{
    public static $defaultName = 'aw:clean-raflight';

    private LoggerInterface $logger;

    private Connection $connection;

    private Connection $unbufferedConnection;

    public function __construct(
        LoggerInterface $logger,
        Connection $connection,
        $replicaUnbufferedConnection
    ) {
        parent::__construct();
        $this->logger = $logger;
        $this->connection = $connection;
        $this->unbufferedConnection = $replicaUnbufferedConnection;
    }

    protected function configure()
    {
        $this->setDescription('Cleaning out RAFlight');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $batcher = new BatchUpdater($this->connection);
        $q = $this->unbufferedConnection->executeQuery(/** @lang MySQL */ "SELECT RAFlightID, Provider, MileCost, StandardItineraryCOS, MileCost, CostPerHour FROM RAFlight WHERE RAFlightID >= 95869932");
        $sql = "DELETE FROM RAFlight WHERE RAFlightID = ?";
        $chain = stmtAssoc($q)
            ->onNthMillis(10000, function ($time, $ticksCounter, $value, $key) {
                $this->logger->info("processed $ticksCounter records..");
            });

        $counter = 0;
        $counterDetails = [];

        foreach ($chain->chunk(10000) as $chunkRows) {
            $params = [];

            foreach ($chunkRows as $row) {
                $hours = $row['MileCost'] / $row['CostPerHour'];
                $toSave = $this->checkHardLimit($row['Provider'], $row['StandardItineraryCOS'], $row['MileCost'],
                    $hours);

                if (!$toSave) {
                    $params[] = [$row['RAFlightID']];
                    $counter++;

                    if (!isset($counterDetails[$row['Provider']][$row['StandardItineraryCOS']])) {
                        $counterDetails[$row['Provider']][$row['StandardItineraryCOS']] = 0;
                    }
                    $counterDetails[$row['Provider']][$row['StandardItineraryCOS']]++;
                }
            }

            if (!empty($params)) {
                $batcher->batchUpdate($params, $sql, 0);
            }

            if (isset($row['RAFlightID'])) {
                $this->logger->info("last processed RAFlightID:" . $row['RAFlightID']);
            }
        }

        $this->logger->info("deleted $counter records");
        $this->logger->info(var_export($counterDetails, true));

        return 0;
    }

    private function checkHardLimit(string $providerCode, string $standardCOS, int $mileCost, float $hours): bool
    {
        $providerId = $this->connection->executeQuery(/** @lang MySQL */ "SELECT ProviderID FROM Provider WHERE Code = ?",
            [$providerCode], [\PDO::PARAM_STR])->fetchOne();

        $limits = $this->connection->executeQuery(/** @lang MySQL */ "SELECT * FROM RAFlightHardLimit WHERE ProviderID = ? AND ClassOfService = ?",
            [$providerId, $standardCOS], [\PDO::PARAM_INT])->fetchAssociative();

        if (empty($limits)) {
            // save all data if there is no information about limits in the RAFlightHardLimit table
            return true;
        }

        // main logic
        return ($mileCost <= $limits['HardCap']) && ($mileCost <= $limits['Base'] + ($limits['Multiplier'] * $hours));
    }
}
