<?php

namespace AwardWallet\MainBundle\Command\CreditCards;

use AwardWallet\MainBundle\Globals\LoggerContext\ContextAwareLoggerWrapper;
use Doctrine\DBAL\Connection;
use Psr\Log\LoggerInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

use function AwardWallet\MainBundle\Globals\Utils\stmt\stmtAssoc;

class IndexMerchantsCommand extends Command
{
    protected static $defaultName = 'aw:index-merchants';
    protected static $defaultDescription = 'index merchants into sphinxsearch';
    private ContextAwareLoggerWrapper $logger;
    private Connection $replicaUnbufferedConnection;
    private Connection $sphinxConnection;

    public function __construct(LoggerInterface $logger, Connection $replicaUnbufferedConnection, Connection $sphinxConnection)
    {
        parent::__construct();

        $this->logger = new ContextAwareLoggerWrapper($logger);
        $this->logger->pushContext(['command' => self::$defaultName]);
        $this->replicaUnbufferedConnection = $replicaUnbufferedConnection;
        $this->sphinxConnection = $sphinxConnection;
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $this->logger->info("starting");

        $q = $this->replicaUnbufferedConnection->executeQuery("select MerchantID, DisplayName from Merchant order by MerchantID");
        $chunkStart = 0;
        $deleted = 0;
        $processed = stmtAssoc($q)
            ->onNthMillisAndLast(5000, function (int $millisFromStart, int $iteration, $currentValue, $currentKey, bool $isLast) {
                $this->logger->info("processed $iteration records, current merchant id: {$currentValue['MerchantID']}");

                if ($isLast) {
                    $this->logger->info("last merchantId is {$currentValue['MerchantID']}");
                }
            })
            ->chunk(5)
            ->onEach(function (array $rows) use (&$chunkStart, &$deleted) {
                $this->sphinxConnection->executeStatement(
                    "replace into Merchant(id, DisplayName) values " . implode(", ", array_fill(0, count($rows), '(?, ?)')),
                    array_merge(...array_map(fn (array $row) => array_values($row), $rows))
                );
                $merchantIds = array_column($rows, 'MerchantID');
                $chunkEnd = max($merchantIds);
                $deleted += $this->sphinxConnection->executeStatement("delete from Merchant where id >= $chunkStart and id <= $chunkEnd and id not in(" . implode(", ", $merchantIds) . ")");
                $chunkStart = $chunkEnd + 1;
            })
            ->count()
        ;

        $this->logger->info("finished, indexed $processed merchants, deleted from index: $deleted");

        return 0;
    }
}
