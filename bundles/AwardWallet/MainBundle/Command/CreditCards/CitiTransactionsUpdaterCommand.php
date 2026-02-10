<?php

namespace AwardWallet\MainBundle\Command\CreditCards;

use Doctrine\DBAL\Connection;
use Psr\Log\LoggerInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

use function AwardWallet\MainBundle\Globals\Utils\iterFluent\it;

class CitiTransactionsUpdaterCommand extends Command
{
    public static $defaultName = 'aw:credit-cards:citi-updater';

    /** @var Connection */
    private $connection;
    /** @var Connection */
    private $replicaConnection;
    /** @var LoggerInterface */
    private $logger;

    public function __construct(LoggerInterface $logger, Connection $connection, Connection $replicaUnbufferedConnection)
    {
        $this->logger = $logger;
        $this->connection = $connection;
        $this->replicaConnection = $replicaUnbufferedConnection;
        parent::__construct();
    }

    protected function configure()
    {
        $this->addOption('dry-run', null, InputOption::VALUE_NONE);
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $dryRun = !empty($input->getOption('dry-run'));
        $insertPackageSize = 25;
        $rates = [
            160 => 5, // 'Air Travel'
            164 => 3, // 'Lodging'
            171 => 5, // 'Restaurants'
            465 => 2, // 'Entertainment'
        ];

        $sqlQuery = "
            SELECT h.*, h.UUID, h.Amount, h.Miles, h.Multiplier, s.CreditCardID
            FROM 
                AccountHistory h
                JOIN SubAccount s 
                ON s.SubAccountID = h.SubAccountID
            WHERE h.PostingDate >= '2019-01-04'
            AND h.ShoppingCategoryID IN (" . implode(array_keys($rates), ",") . ")
            AND s.CreditCardID = 29
        ";

        $result = $this->replicaConnection->executeQuery($sqlQuery);

        $packageQueryRows = [];

        for ($i = 0; $i < $insertPackageSize; $i++) {
            $packageQueryRows[] = "(:UUID{$i}, :MI{$i}, :MU{$i})";
        }

        if (!$dryRun) {
            $insertStmtPackage = $this->connection->prepare("
                INSERT IGNORE INTO AccountHistory (UUID, Miles, Multiplier)
                VALUES " . implode(',', $packageQueryRows) . "
                ON DUPLICATE KEY UPDATE 
                    Miles = VALUES(Miles), 
                    Multiplier = VALUES(Multiplier)
            ");

            $this->connection->beginTransaction();
        }

        $this->logger->info("Searching history rows.");
        $timeToLog = time();
        $count = 0;
        $updateCount = 0;
        $package = [];

        while ($row = $result->fetch()) {
            if ($timeToLog + 30 < time()) {
                $this->logger->info(sprintf("AccountHistory. %s rows processed.", $count));
                $timeToLog = time();
            }
            $count++;

            $categoryId = (int) $row["ShoppingCategoryID"];
            $multiplier = $rates[$categoryId];
            $miles = round((float) $row["Amount"] * $multiplier, 2);

            $i = count($package);
            $package[] = [
                ":UUID" . $i => $row["UUID"],
                ":MI" . $i => $miles,
                ":MU" . $i => $multiplier,
            ];
            $updateCount++;

            if ($i + 1 < $insertPackageSize) {
                continue;
            }

            if (!$dryRun) {
                $insertStmtPackage->execute(
                    it($package)->flatten(1)->toArrayWithKeys()
                );
                $this->connection->commit();
                $this->connection->beginTransaction();
            }
            $package = [];
        }

        if (count($package) > 0 && !$dryRun) {
            $this->connection->executeUpdate(
                "
                    INSERT IGNORE INTO AccountHistory (UUID, Miles, Multiplier)
                    VALUES " . implode(',', array_slice($packageQueryRows, 0, count($package))) . "
                    ON DUPLICATE KEY UPDATE 
                        Miles = VALUES(Miles), 
                        Multiplier = VALUES(Multiplier)
                ",
                it($package)->flatten(1)->toArrayWithKeys()
            );
        }

        $this->logger->info(sprintf("AccountHistory. Processed %s. Updated %s.", $count, $updateCount));

        return 0;
    }
}
