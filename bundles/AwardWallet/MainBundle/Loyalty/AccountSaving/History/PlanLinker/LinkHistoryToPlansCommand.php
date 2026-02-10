<?php

namespace AwardWallet\MainBundle\Loyalty\AccountSaving\History\PlanLinker;

use AwardWallet\MainBundle\FrameworkExtension\Command;
use AwardWallet\MainBundle\Service\ProgressLogger;
use Psr\Log\LoggerInterface;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class LinkHistoryToPlansCommand extends Command
{
    public static $defaultName = 'aw:link-history-to-plans';
    /**
     * @var MatcherFactory
     */
    private $matcherFactory;
    /**
     * @var DataSourceFactory
     */
    private $dataSourceFactory;
    /**
     * @var LoggerInterface
     */
    private $logger;
    /**
     * @var Writer
     */
    private $writer;
    /**
     * @var Cleaner
     */
    private $cleaner;

    public function __construct(
        MatcherFactory $matcherFactory,
        DataSourceFactory $dataSourceFactory,
        LoggerInterface $logger,
        Writer $writer,
        Cleaner $cleaner
    ) {
        parent::__construct();
        $this->matcherFactory = $matcherFactory;
        $this->dataSourceFactory = $dataSourceFactory;
        $this->logger = $logger;
        $this->writer = $writer;
        $this->cleaner = $cleaner;
    }

    public function configure()
    {
        $this
            ->addOption('provider', null, InputOption::VALUE_REQUIRED, 'provider code')
        ;
    }

    public function execute(InputInterface $input, OutputInterface $output)
    {
        $provider = $input->getOption('provider');

        if (empty($provider)) {
            throw new \Exception("provider required");
        }

        $matcher = $this->matcherFactory->getMatcher($provider);

        if ($matcher === null) {
            throw new \Exception("No matcher found for provider $provider");
        }

        $dataSource = $this->dataSourceFactory->getDataSource($provider);

        if ($dataSource === null) {
            throw new \Exception("No data source found for provider $provider");
        }

        $startTime = time();
        $this->reindex($provider, $dataSource, $matcher);
        $this->cleaner->cleanProvider($provider, $startTime);
        $this->logger->info("done");
    }

    private function reindex(string $provider, DataSourceInterface $dataSource, MatcherInterface $matcher)
    {
        $progress = new ProgressLogger($this->logger, 100, 30);
        $count = 0;
        $matchCount = 0;

        foreach ($dataSource->getRows($provider) as $row) {
            $progress->showProgress("reindexing $provider", $count);
            $matches = $matcher->findMatchingItineraries($provider, $row['UserID'], $row['UserAgentID'], $row);

            foreach ($matches as $match) {
                $this->writer->saveMatch($row["UUID"], $match);
                $matchCount++;
            }
            $count++;
        }
        $this->logger->info("processed {$count} rows, saved {$matchCount} matches");
    }
}
