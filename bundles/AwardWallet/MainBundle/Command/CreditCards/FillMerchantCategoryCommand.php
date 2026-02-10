<?php

namespace AwardWallet\MainBundle\Command\CreditCards;

use AwardWallet\MainBundle\Entity\Repositories\ParameterRepository;
use AwardWallet\MainBundle\Service\CreditCards\MerchantCategoryDetector;
use AwardWallet\MainBundle\Service\ProgressLogger;
use Doctrine\DBAL\Connection;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class FillMerchantCategoryCommand extends Command
{
    use WaitReplicaSyncTrait;

    public static $defaultName = 'aw:credit-cards:fill-merchant-category';

    /** @var Connection */
    private $mainConnection;
    /** @var Connection */
    private $replicaConnection;
    /** @var LoggerInterface */
    private $logger;
    /** @var ParameterRepository */
    private $paramRepository;
    /** @var EntityManagerInterface */
    private $em;
    /** @var ProgressLogger */
    private $progressLogger;
    private MerchantCategoryDetector $merchantCategoryDetector;

    public function __construct(
        LoggerInterface $logger,
        Connection $mainConnection,
        Connection $replicaUnbufferedConnection,
        ParameterRepository $paramRepository,
        EntityManagerInterface $em,
        MerchantCategoryDetector $merchantCategoryDetector
    ) {
        $this->logger = $logger;
        $this->mainConnection = $mainConnection;
        $this->replicaConnection = $replicaUnbufferedConnection;
        $this->paramRepository = $paramRepository;
        $this->em = $em;
        $this->progressLogger = new ProgressLogger($this->logger, 100, 20);
        parent::__construct();
        $this->merchantCategoryDetector = $merchantCategoryDetector;
    }

    protected function configure()
    {
        $this
            ->addOption('dry-run', null, InputOption::VALUE_NONE)
            ->addOption('merchantId', null, InputOption::VALUE_REQUIRED, 'process only this merchantId');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $this->logger->info("Getting merchants");
        $dryRun = !empty($input->getOption('dry-run'));

        if ($dryRun) {
            $this->logger->info("dry run");
        }

        $merchantId = (int) $input->getOption('merchantId');

        $merchants = $this->replicaConnection->executeQuery("
            select MerchantID, Transactions from Merchant 
            where Transactions > 1 
            " . ($merchantId ? " and MerchantID = {$merchantId}" : "") . "
            order by Transactions DESC
        ")->fetchAll(\PDO::FETCH_KEY_PAIR);

        $count = 0;
        $merchantUpdateResult = [];

        foreach ($merchants as $mid => $merchantTransactions) {
            $mid = (int) $mid;
            $merchantTransactions = (int) $merchantTransactions;
            $this->progressLogger->showProgress("processed merchants", $count);
            $merchantUpdateResult[$mid] = $this->merchantCategoryDetector->detectCategory($mid, $merchantTransactions);
            $count++;
        }

        $this->logger->info("$count merchants processed.");

        // -------- updating logic
        $this->logger->info("loading merchants");
        $merchantCategories = $this->replicaConnection->executeQuery(
            "SELECT MerchantID, ShoppingCategoryID FROM Merchant" . ($merchantId ? " where MerchantID = {$merchantId}" : "")
        )->fetchAll(\PDO::FETCH_KEY_PAIR);
        $this->logger->info("loaded " . count($merchantCategories) . " merchants");
        $merchantCategories = array_map(function ($category) {
            if ($category === null) {
                return $category;
            }

            return (int) $category;
        }, $merchantCategories);

        $this->logger->info("Merchant. Updating ShoppingCategoryID");
        $updateCount = 0;
        $newCategoryCount = 0;
        $rowCount = 0;
        $timeToLog = time();
        $this->mainConnection->beginTransaction();

        foreach ($merchantUpdateResult as $merchant => $category) {
            $existingCategory = ($merchantCategories[$merchant] ?? null);

            if ($category !== $existingCategory) {
                if (!$dryRun) {
                    if ($updateCount === 0 && $newCategoryCount === 0) {
                        $updateMerchant = $this->mainConnection->prepare("UPDATE Merchant SET ShoppingCategoryID = :CategoryID WHERE MerchantID = :MerchantID");
                    }
                    $updateMerchant->execute(['MerchantID' => $merchant, 'CategoryID' => $category]);
                }

                if ($existingCategory === null) {
                    $newCategoryCount++;
                } else {
                    $updateCount++;
                }

                if (($updateCount % 10) === 0 || ($newCategoryCount % 10) === 0) {
                    $this->mainConnection->commit();
                    $this->mainConnection->beginTransaction();
                }
            }
            unset($merchantCategories[$merchant]);
            $rowCount++;

            if ($timeToLog + 30 < time()) {
                $this->logger->info("Updating... $rowCount rows processed.");
                $this->mainConnection->executeQuery("select 1"); // prevent timeouts
                $timeToLog = time();
            }
        }
        $this->mainConnection->commit();
        $this->logger->info("Done, processed $rowCount rows, updated $updateCount merchants, set from null: $newCategoryCount");

        if ($merchantId !== 0) {
            $merchantCategories = array_filter($merchantCategories, function ($category) {
                return $category !== null;
            });
            $merchants = array_keys($merchantCategories);
            $this->logger->info("Merchant. Unsetting ShoppingCategoryID, to unset: " . count($merchants));
            $updateCount = 0;

            while (!empty($merchants)) {
                $batch = array_splice($merchants, 0, 100, []);

                if (!$dryRun) {
                    $this->mainConnection->executeQuery("
                UPDATE Merchant 
                SET ShoppingCategoryID = NULL 
                WHERE MerchantID in (?)", [$batch], [Connection::PARAM_INT_ARRAY]);
                }
                $updateCount += count($batch);

                if ($timeToLog + 30 < time()) {
                    $this->logger->info("Updating to null... $updateCount rows updated.");
                    $timeToLog = time();
                }
            }
            $this->logger->info("Done, $updateCount merchants categories set to null");
        }

        // -------- END updating logic
        return 0;
    }
}
