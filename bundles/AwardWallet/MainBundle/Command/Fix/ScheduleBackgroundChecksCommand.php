<?php

namespace AwardWallet\MainBundle\Command\Fix;

use AwardWallet\MainBundle\Entity\Repositories\UsrRepository;
use AwardWallet\MainBundle\Entity\Usr;
use AwardWallet\MainBundle\Service\BackgroundCheckScheduler;
use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Result;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class ScheduleBackgroundChecksCommand extends Command
{
    public static $defaultName = 'aw:fix:schedule-background-checks';

    private Connection $connection;
    private EntityManagerInterface $entityManager;
    private LoggerInterface $logger;
    private BackgroundCheckScheduler $backgroundCheckScheduler;
    private UsrRepository $usrRepository;

    public function __construct(
        Connection $connection,
        EntityManagerInterface $entityManager,
        LoggerInterface $logger,
        BackgroundCheckScheduler $backgroundCheckScheduler,
        UsrRepository $usrRepository
    ) {
        parent::__construct();

        $this->connection = $connection;
        $this->entityManager = $entityManager;
        $this->logger = $logger;
        $this->backgroundCheckScheduler = $backgroundCheckScheduler;
        $this->usrRepository = $usrRepository;
    }

    protected function configure()
    {
        $this
            ->setDescription('Schedule background checks for free users')
            ->addOption('batch-size', 'b', InputOption::VALUE_REQUIRED, 'Number of users to process in one batch', 500);
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $batchSize = (int) $input->getOption('batch-size');

        $sql = '
            SELECT DISTINCT UserID
            FROM Account
            WHERE
                ProviderID IS NOT NULL
                AND State <> :state
        ';

        $stmt = $this->connection->executeQuery($sql, [
            'state' => ACCOUNT_DISABLED,
        ]);
        $totalUsers = 0;

        while ($batch = $this->fetchBatch($stmt, $batchSize)) {
            $processedCount = count($batch);

            if ($processedCount > 0) {
                $output->writeln(sprintf('processing batch %d-%d...', $totalUsers + 1, $totalUsers + $processedCount));
            }

            $this->processBatch($batch);
            $totalUsers += $processedCount;

            $this->entityManager->clear();
        }

        $this->logger->info("schedule background checks completed. Total users processed: {$totalUsers}");

        return 0;
    }

    private function fetchBatch(Result $stmt, int $batchSize): array
    {
        $batch = [];
        $count = 0;

        while ($row = $stmt->fetchAssociative()) {
            $batch[] = $row['UserID'];
            $count++;

            if ($count >= $batchSize) {
                break;
            }
        }

        return $batch;
    }

    private function processBatch(array $userIds): void
    {
        foreach ($userIds as $userId) {
            /** @var Usr|null $user */
            $user = $this->usrRepository->find($userId);

            if (!$user) {
                continue;
            }

            $this->backgroundCheckScheduler->scheduleAccountsByUser($user);
        }
    }
}
