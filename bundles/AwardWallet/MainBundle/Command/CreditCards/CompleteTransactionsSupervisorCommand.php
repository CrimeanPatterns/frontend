<?php

namespace AwardWallet\MainBundle\Command\CreditCards;

use Psr\Log\LoggerInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Process\Process;

class CompleteTransactionsSupervisorCommand extends Command
{
    public const DEFAULT_MONTHS_COUNTER = 36;
    public const PROCESS_STARTING_TIME_LIMIT = 60;
    public const PROCESS_KILLING_TIME_LIMIT = 5;

    public static $defaultName = 'aw:credit-cards:supervisor-complete-transactions';
    /** @var LoggerInterface */
    private $logger;
    /** @var array Process[] */
    private $threads = [];

    public function __construct(LoggerInterface $logger)
    {
        $this->logger = $logger;
        parent::__construct();
    }

    protected function configure()
    {
        $this
            ->addOption('threads', null, InputOption::VALUE_REQUIRED)
            ->addOption('fetch-only', null, InputOption::VALUE_NONE)
            ->addOption('dry-run', null, InputOption::VALUE_NONE)
            ->addOption('months', null, InputOption::VALUE_OPTIONAL, 'Months counter to process history rows from current month', self::DEFAULT_MONTHS_COUNTER)
            ->addOption('begin', null, InputOption::VALUE_OPTIONAL, 'Start month to process. Format:\'YYYY-MM\'', null)
        ;
    }

    /**
     * @throws \Exception
     */
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        pcntl_async_signals(true);
        pcntl_signal(SIGTERM, function ($signal) {
            $this->logger->notice("got SIGTERM, stopping children and exiting supervisor");
            $this->stopAllThreads();
        });

        $threadsLimit = $input->getOption('threads');
        $months = $input->getOption('months');
        $begin = $input->getOption('begin');
        $fetchOnly = $input->getOption('fetch-only');
        $dryRun = $input->getOption('dry-run');

        if ($begin && preg_match("#^\d{4}-\d{2}$#", $begin)) {
            $processingDate = new \DateTime($begin);
        } else {
            $processingDate = new \DateTime();
        }

        $this->logger->info("Starting at " . $processingDate->format('n-Y'));

        for ($i = 1; $i <= $months; $i++) {
            while (true) {
                $this->monitoring();

                if (!$this->canStart($threadsLimit)) {
                    sleep(5);

                    continue;
                }

                $started = $this->startThread($processingDate->format('Y'), $processingDate->format('n'), $fetchOnly, $dryRun);

                if ($started) {
                    break;
                } else {
                    $this->stopAllThreads();

                    break 2;
                }
            }
            $processingDate->sub(new \DateInterval('P1M'));
        }

        while (count($this->threads) > 0) {
            $this->monitoring();
            sleep(5);
        }

        $this->logger->info('Done!!');

        return 0;
    }

    private function stopAllThreads()
    {
        $this->logger->error("Force stopping all threads.");

        /** @var Process $thread */
        foreach (array_keys($this->threads) as $pid) {
            $this->stopThread($pid);
        }
    }

    private function stopThread(int $pid)
    {
        /** @var Process $thread */
        $thread = $this->threads[$pid];

        if ($thread->isRunning()) {
            $thread->signal(SIGTERM);
            $startTime = time();

            while (
                $thread->isRunning()
                && time() - $startTime < self::PROCESS_KILLING_TIME_LIMIT
            ) {
                usleep(500000);
            }

            if ($thread->isRunning()) {
                $thread->signal(SIGKILL);
            }
        }

        unset($this->threads[$pid]);
    }

    private function monitoring()
    {
        /** @var Process $thread */
        foreach ($this->threads as $key => $thread) {
            echo $thread->getOutput();

            if (!$thread->isRunning()) {
                $this->stopThread($key);
            }
        }
    }

    private function canStart(int $threadsLimit): bool
    {
        if (count($this->threads) < $threadsLimit) {
            return true;
        } else {
            return false;
        }
    }

    /**
     * @param int $year
     * @param int $month
     * @return int PID
     */
    private function startThread($year, $month, $fetchOnly = false, $dryRun = false): int
    {
        $fetchOnlyParam = $fetchOnly ? "--fetch-only" : "";
        $dryRunParam = $dryRun ? "--dry-run" : "";

        $thread = new Process("exec app/console aw:credit-cards:complete-transactions {$dryRunParam} --update {$fetchOnlyParam} --year {$year} --month {$month} -vv");

        $thread->start(function ($type, $buffer) {
            echo $buffer;
        });

        $this->logger->info("Starting thread {$month}-{$year}");
        $startTime = time();

        while (
            !$thread->isRunning()
            && is_null($thread->getExitCode())
            && time() - $startTime < self::PROCESS_STARTING_TIME_LIMIT
        ) {
            usleep(500000);
        }

        if (!$thread->isRunning()) {
            $this->logger->error('Can`t start thread', ['commandLine' => $thread->getCommandLine()]);
            $this->stopAllThreads();

            return false;
        }

        $this->threads[$thread->getPid()] = $thread;
        $this->logger->info("Thread {$month}-{$year} started. PID " . $thread->getPid());

        return true;
    }
}
