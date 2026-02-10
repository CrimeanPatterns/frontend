<?php

namespace AwardWallet\MainBundle\Service\CreditCards\Commands;

use AwardWallet\MainBundle\Entity\Repositories\ParameterRepository;
use AwardWallet\MainBundle\Service\CreditCards\Commands\Helpers\SnapshotTable;
use AwardWallet\MainBundle\Service\CreditCards\Commands\Helpers\SnapshotTablesEnumerator;
use Clock\ClockInterface;
use Doctrine\DBAL\Connection;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

use function AwardWallet\MainBundle\Globals\Utils\iterFluent\it;

class MerchantTransactionsClearOldTablesCommand extends Command
{
    protected static $defaultName = 'aw:merchant:transactions:clear-old-tables';
    private Connection $dbConnection;
    private ClockInterface $clock;
    private ParameterRepository $paramRepository;
    private SnapshotTablesEnumerator $snapshotTablesEnumerator;

    public function __construct(
        Connection $dbConnection,
        ParameterRepository $paramRepository,
        ClockInterface $clock,
        SnapshotTablesEnumerator $snapshotTablesEnumerator
    ) {
        parent::__construct();
        $this->dbConnection = $dbConnection;
        $this->clock = $clock;
        $this->paramRepository = $paramRepository;
        $this->snapshotTablesEnumerator = $snapshotTablesEnumerator;
    }

    public static function extractDate(string $input, string $prefix): \DateTimeInterface
    {
        if (\preg_match('#' . \preg_quote($prefix, '#') . '(\d{4})(\d{2})(\d{2})(\d{2})(\d{2})(\d{2})#', $input, $matches)) {
            [$_, $year, $month, $day, $hour, $minute, $seconds] = $matches;

            return new \DateTime("{$year}-{$month}-{$day} {$hour}:{$minute}:{$seconds}");
        }

        throw new \RuntimeException("Invalid date format for: {$input}");
    }

    protected function configure()
    {
        $this
            ->setDescription('Remove old merchant transactions tables')
            ->addOption('dry-run', 'd', InputOption::VALUE_NONE, 'Do not remove tables, just show what would be removed')
            ->addOption('merchant-days', null, InputOption::VALUE_REQUIRED, 'Remove merchant tables older than given days', 3)
            ->addOption('transaction-days', null, InputOption::VALUE_REQUIRED, 'Remove transactions tables older than given hours');
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $transactionTableSuffix = $this->paramRepository->getParam(ParameterRepository::LAST_TRANSACTIONS_DATE);

        if (null === $transactionTableSuffix) {
            $output->writeln("No transactions date stored in Params");

            return 0;
        }

        $merchantTableSuffix = $this->paramRepository->getParam(ParameterRepository::MERCHANT_EXAMPLES_DATE);

        if (null === $merchantTableSuffix) {
            $output->writeln("No merchant date stored in Params");

            return 0;
        }

        $current = $this->clock->current()->getAsDateTimeImmutable();
        $output->writeln("Transaction table: " . $transactionTableSuffix);
        $transactionRightDate = SnapshotTablesEnumerator::extractDate($transactionTableSuffix);
        $transactionDays = (int) $input->getOption('transaction-days');
        $transactionMinDate = \min(
            $current->modify("-{$transactionDays} day"),
            $transactionRightDate->modify("-{$transactionDays} day")
        );

        $output->writeln("Merchant table: " . $merchantTableSuffix);
        $merchantLeftDate = SnapshotTablesEnumerator::extractDate($merchantTableSuffix);
        $merchantDays = (int) $input->getOption('merchant-days');
        $merchantMinDate = \min(
            $current->modify("-{$merchantDays} day"),
            $merchantLeftDate->modify("-{$merchantDays} day")
        );

        $isDryRun = $input->getOption('dry-run');
        $removedTables = 0;

        /** @var SnapshotTable $tableIterator */
        foreach (
            [
                $this->makeTablesExtractor('MerchantRematchTransactionsExamples%', $merchantMinDate),
                $this->makeTablesExtractor('LastTransactionsExamples%', $transactionMinDate),
            ] as $tableIterator
        ) {
            foreach ($tableIterator as $snapshotTable) {
                if ($isDryRun) {
                    $output->write("Would remove table {$snapshotTable->getName()}...");
                    $output->writeln('DONE');
                } else {
                    $output->write("Removing table {$snapshotTable->getName()}...");
                    $this->dbConnection->executeQuery('drop table ' . $snapshotTable->getName());
                    $output->writeln('DONE');
                }

                $removedTables++;
            }
        }

        $output->writeln("Tables removed: " . $removedTables);

        return 0;
    }

    /**
     * @return iterable<SnapshotTable>
     */
    private function makeTablesExtractor(string $prefix, \DateTimeInterface $minDate): iterable
    {
        return
            it($this->snapshotTablesEnumerator->enumerate($prefix))
            ->takeWhile(fn (SnapshotTable $snapshotTable) => $snapshotTable->getMaxDate() < $minDate);
    }
}
