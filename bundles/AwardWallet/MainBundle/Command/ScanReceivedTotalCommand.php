<?php

namespace AwardWallet\MainBundle\Command;

use AwardWallet\MainBundle\Globals\LoggerContext\ContextAwareLoggerWrapper;
use AwardWallet\MainBundle\Service\BestCreditCards\ReceivedTotalLinker;
use Doctrine\DBAL\Connection;
use Psr\Log\LoggerInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

use function AwardWallet\MainBundle\Globals\Utils\stmt\stmtAssoc;

class ScanReceivedTotalCommand extends Command
{
    protected static $defaultName = 'aw:scan-received-total';
    private ContextAwareLoggerWrapper $logger;
    private Connection $unbufferedConnection;
    private ReceivedTotalLinker $linker;

    public function __construct(LoggerInterface $logger, Connection $replicaUnbufferedConnection, ReceivedTotalLinker $linker)
    {
        parent::__construct();

        $this->logger = new ContextAwareLoggerWrapper($logger);
        $this->logger->pushContext(['command' => self::$defaultName]);
        $this->unbufferedConnection = $replicaUnbufferedConnection;
        $this->linker = $linker;
    }

    public function configure()
    {
        $this->addOption('userId', null, InputOption::VALUE_REQUIRED);
    }

    public function execute(InputInterface $input, OutputInterface $output)
    {
        $this->logger->info("scanning received total");
        $changed = stmtAssoc($this->unbufferedConnection->executeQuery(
            "select * from ReceivedTotal"
            . ($input->getOption('userId') ? " where UserID = " . intval($input->getOption('userId')) : "")
        ))
            ->onNthMillisAndLast(5000, function (int $millisFromStart, int $iteration, $currentValue, $currentKey, bool $isLast) {
                $this->logger->info("processed $iteration records, current: {$currentValue['ReceivedTotalID']}, {$currentValue['ReceiveDate']}");
            })
            ->map(function (array $row) {
                return $this->linker->link($row['UserID'], $row['Total'], new \DateTime($row['ReceiveDate']), $row['ReceivedTotalID'])
                    ? 1 : 0;
            })
            ->sum()
        ;

        $this->logger->info("done, changed records: {$changed}");

        return 0;
    }
}
