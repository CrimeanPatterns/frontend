<?php

namespace AwardWallet\MainBundle\Service\CreditCards\Commands;

use AwardWallet\MainBundle\Entity\Repositories\ProviderRepository;
use Clock\ClockInterface;
use Doctrine\DBAL\Connection;
use Duration\Duration;
use Psr\Log\LoggerInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

use function AwardWallet\MainBundle\Globals\Utils\stmt\stmtColumn;

class ClearCategoriesCommand extends Command
{
    public static $defaultName = 'aw:credit-cards:clear-categories';
    private Connection $dbConnection;
    private ProviderRepository $providerRep;
    private ClockInterface $clock;
    private LoggerInterface $logger;
    private Connection $replicaUnbufferedConnection;

    public function __construct(
        Connection $replicaUnbufferedConnection,
        Connection $dbConnection,
        ProviderRepository $providerRep,
        ClockInterface $clock,
        LoggerInterface $logger
    ) {
        parent::__construct();

        $this->dbConnection = $dbConnection;
        $this->providerRep = $providerRep;
        $this->clock = $clock;
        $this->logger = $logger;
        $this->replicaUnbufferedConnection = $replicaUnbufferedConnection;
    }

    protected function configure()
    {
        $this
            ->addArgument('provider-code', InputArgument::REQUIRED, 'provider code')
            ->addOption('start-date', null, InputOption::VALUE_REQUIRED, 'start date')
            ->addOption('end-date', null, InputOption::VALUE_REQUIRED, 'end date')
            ->addOption('update-chunk-size', null, InputOption::VALUE_REQUIRED, 'update chunk size', 1_000);
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $providerCode = $input->getArgument('provider-code');
        $providerId = $this->providerRep->findOneByCode($providerCode)->getId();
        $startDate = $this->tryCreateDate($input->getOption('start-date'));
        $endDate = $this->tryCreateDate($input->getOption('end-date') ?? 'now');

        $params = [$providerId];
        $dateWhere = '';

        if (null !== $startDate) {
            $dateWhere .= ' and a.UpdateDate >= ?';
            $params[] = $startDate;
        }

        if (null !== $endDate) {
            $dateWhere .= ' and a.UpdateDate < ?';
            $params[] = $endDate;
        }

        $updateChunkSuze = (int) $input->getOption('update-chunk-size');

        $output->write('Opening main query...');
        $uuidsStmt = $this->replicaUnbufferedConnection->executeQuery(
            "select ah.UUID 
            from Account a
            join AccountHistory ah on a.AccountID = ah.AccountID
            where 
                a.ProviderID = ?
                {$dateWhere}  
                and ah.Category is not null and ah.Category <> ''",
            $params
        );
        $output->writeln('processing...');

        $lastStopIteration = 0;
        $lastStopTime = $startTime = $this->clock->current();
        $affectedCount = 0;

        foreach (
            stmtColumn($uuidsStmt)
            ->onNthMillisAndLast(
                10_000,
                $this->makePeriodicLogger($output, $startTime, $lastStopIteration, $lastStopTime)
            )
            ->chunk($updateChunkSuze) as $uuidChunk
        ) {
            $affectedCount += $this->dbConnection->executeStatement(
                "update AccountHistory ah
                    join Account a on a.AccountID = ah.AccountID
                    set ah.Category = null, ah.ShoppingCategoryID = null, ah.MerchantID = null
                    where ah.`UUID` in (?) {$dateWhere} and ah.Category is not null and ah.Category <> ''",
                \array_merge(
                    [$uuidChunk],
                    null !== $startDate ? [$startDate] : [],
                    null !== $endDate ? [$endDate] : [],
                ),
                \array_merge(
                    [Connection::PARAM_STR_ARRAY],
                    null !== $startDate ? [\PDO::PARAM_STR] : [],
                    null !== $endDate ? [\PDO::PARAM_STR] : [],
                )
            );
        }

        $output->writeln('total affected: ' . $affectedCount);
    }

    protected function tryCreateDate(?string $date): ?string
    {
        if (null === $date) {
            return null;
        }

        return (new \DateTimeImmutable($date))->format('Y-m-d H:i:s');
    }

    private function makePeriodicLogger(OutputInterface $output, Duration $startTime, int &$lastStopIteration, Duration &$lastStopTime)
    {
        return function ($_1, int $iteration, $_2, $_3, bool $isLast) use (
            &$lastStopIteration,
            &$lastStopTime,
            $startTime,
            $output
        ) {
            $stopTime = $this->clock->current();
            $output->writeln(
                'running for: '
                . \number_format($stopTime->sub($startTime)->getAsMinutesFractionFloat(), 2)
                . ' min(s), '
                . "read: " . \number_format($iteration) . " records, "
                . "speed: " . \number_format(($iteration - $lastStopIteration) / 1000 / $stopTime->sub($lastStopTime)->getAsSecondsFractionFloat(), 2)
                . " K/sec, "
                . "memory " . \number_format((int) \round(\memory_get_usage(true) / (1024 * 1024))) . " MB"
            );
            $lastStopIteration = $iteration;
            $lastStopTime = $stopTime;
        };
    }
}
