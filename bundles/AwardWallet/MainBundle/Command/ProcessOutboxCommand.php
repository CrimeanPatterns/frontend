<?php

namespace AwardWallet\MainBundle\Command;

use AwardWallet\MainBundle\FrameworkExtension\Command;
use AwardWallet\MainBundle\Globals\UnbufferedConnectionFactory;
use AwardWallet\MainBundle\Service\OutboxProcessorInterface;
use Doctrine\DBAL\Connection;
use Doctrine\DBAL\ParameterType;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

use function AwardWallet\MainBundle\Globals\Utils\iterFluent\it;

class ProcessOutboxCommand extends Command
{
    protected static $defaultName = 'aw:process-outbox';
    private UnbufferedConnectionFactory $connectionFactory;
    /**
     * @var array<int, OutboxProcessorInterface>
     */
    private array $processorsMap;

    /**
     * @param iterable<OutboxProcessorInterface> $outboxProcessors
     */
    public function __construct(
        UnbufferedConnectionFactory $connectionFactory,
        iterable $outboxProcessors
    ) {
        parent::__construct();

        foreach ($outboxProcessors as $outboxProcessor) {
            foreach ($outboxProcessor->getSupportedOutboxTypes() as $type) {
                $this->processorsMap[$type] = $outboxProcessor;
            }
        }
        $this->connectionFactory = $connectionFactory;
    }

    protected function configure()
    {
        $this
            ->addOption('batch-size', 'b', InputOption::VALUE_REQUIRED, 'batch size', 100)
            ->addOption('type', 't', InputOption::VALUE_IS_ARRAY | InputOption::VALUE_REQUIRED, 'type(s) to process', [])
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $limit = (int) $input->getOption('batch-size');
        $types = it($input->getOption('type'))
            ->map(fn ($type) => (int) $type)
            ->toArray();
        $totalProcessedRows = 0;
        $noProcessorRows = 0;
        $connection = $this->connectionFactory->createConnection();

        while (true) {
            $connection->beginTransaction();

            try {
                $outboxRows = $connection
                    ->executeQuery("
                        select * from Outbox " . ($types ? " where TypeID in (:types)" : "") . "
                        limit :limit
                        for update skip locked",
                        \array_merge(
                            ['limit' => $limit],
                            $types ? ['types' => $types] : []
                        ),
                        \array_merge(
                            ['limit' => ParameterType::INTEGER],
                            $types ? ['types' => Connection::PARAM_INT_ARRAY] : []
                        )
                    )
                    ->fetchAllAssociative();

                if (!$outboxRows) {
                    break;
                }

                foreach ($outboxRows as $outboxRow) {
                    $type = $outboxRow['TypeID'];
                    $processor = $this->processorsMap[$type] ?? null;

                    if ($processor) {
                        $processor->process($outboxRow);
                    } else {
                        $noProcessorRows++;
                    }
                }

                $connection->executeStatement(
                    'delete from Outbox where OutboxID in (:ids)',
                    ['ids' => \array_column($outboxRows, 'OutboxID')],
                    ['ids' => Connection::PARAM_INT_ARRAY]
                );
            } catch (\Throwable $e) {
                $connection->rollBack();

                throw $e;
            }

            $connection->commit();
            $totalProcessedRows += \count($outboxRows);
            $output->writeln('Processed ' . $totalProcessedRows . ' row(s), ' . $noProcessorRows . ' row(s) without processor');
        }

        $output->writeln('[TOTAL] Processed ' . $totalProcessedRows . ' row(s), ' . $noProcessorRows . ' row(s) without processor');

        return 0;
    }
}
