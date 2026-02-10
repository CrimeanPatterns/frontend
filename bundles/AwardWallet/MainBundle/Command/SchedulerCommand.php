<?php

namespace AwardWallet\MainBundle\Command;

use AwardWallet\MainBundle\Entity\Repositories\ParameterRepository;
use Doctrine\DBAL\Connection;
use Psr\Log\LoggerInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Process\Exception\RuntimeException;
use Symfony\Component\Process\Process;

class SchedulerCommand extends Command
{
    protected static $defaultName = 'aw:scheduler';

    private OutputInterface $output;
    private LoggerInterface $logger;
    private Connection $connnection;
    private \Memcached $memcached;
    private ParameterRepository $paramRepo;

    public function __construct(
        LoggerInterface $logger,
        Connection $connection,
        \Memcached $memcached,
        ParameterRepository $paramRepo
    ) {
        parent::__construct();

        $this->logger = $logger;
        $this->connnection = $connection;
        $this->memcached = $memcached;
        $this->paramRepo = $paramRepo;
    }

    public function configure()
    {
        $this
            ->setName("aw:scheduler")
            ->setDescription('run periodic jobs');
    }

    public function execute(InputInterface $input, OutputInterface $output): int
    {
        $this->output = $output;

        declare(ticks=1);

        $output->writeln("scheduler started. this script will not stop, terminate it ctrl-c");

        pcntl_signal(SIGTERM, function () {
            $this->logger->info("scheduled stopped");

            exit;
        });

        $heartbeat = time();

        while (true) {
            if ($this->memcached->add("aw_scheduler_1_min", gethostname(), 600)) {
                $this->logger->info("running 1 minute jobs");

                if (empty($this->paramRepo->getParam(ParameterRepository::BACKGROUND_CHECK_DISABLED))) {
                    $this->exec("app/console aw:check-balances -vv", 300);
                }
                $this->memcached->set("aw_scheduler_1_min", gethostname(), 60);
            }

            if ($this->memcached->add("aw_scheduler_5_min", gethostname(), 300)) {
                $this->logger->info("running 5 minute jobs");

                $this->exec("app/console aw:balancewatch-update -vv", 290);
            }

            if ($this->memcached->add("aw_scheduler_10_min", gethostname(), 600)) {
                $this->logger->info("running 10 minute jobs");

                $this->exec("app/console aw:ra:send-search-results-notification -vv", 590);
            }

            if ($this->memcached->add("aw_scheduler_30_min", gethostname(), 60 * 30)) {
                $this->logger->info("running 30 minute jobs");

                if (empty($this->paramRepo->getParam(ParameterRepository::SEMI_HOURLY_DISABLED))) {
                    $this->exec("util/semiHourlyJobs.sh", 60 * 25);
                }

                if (empty($this->paramRepo->getParam(ParameterRepository::CHECKIN_DISABLED))) {
                    $this->exec("app/console aw:flight-notification:produce", 60 * 25);
                }

                // $this->scanBookerMailboxes();
            }

            if ($this->memcached->add("aw_scheduler_1_hour", gethostname(), 60 * 60)) {
                $this->logger->info("running 1 hour jobs");

                $this->exec("app/console aw:ra-check-changes -vv", 300);
            }
            sleep(random_int(3, 6));

            if ((time() - $heartbeat) >= 60) {
                $this->logger->info("heartbeat");
                $heartbeat = time();
            }
        }

        return 0;
    }

    private function exec(string $command, int $timeout, bool $wait = true)
    {
        $startTime = time();
        $this->logger->info($command);
        $process = Process::fromShellCommandline($command, null, null, null, $timeout);

        try {
            $process->start($wait ? function ($type, $data) {
                $this->logger->info(trim($data));
            } : null);

            if ($wait) {
                $process->wait();
            }
        } catch (RuntimeException $e) {
            $this->logger->critical("{$command} failed with code " . $e->getCode() . ", " . $e->getMessage());
            posix_kill($process->getPid(), SIGTERM);
            sleep(3);
            posix_kill($process->getPid(), SIGKILL);
        }
        $this->logger->info("done: $command", ["time" => time() - $startTime]);
    }

    // broken for months and no one noticed, disabling
    //    private function scanBookerMailboxes(): void
    //    {
    //        $bookers = $this->connnection->executeQuery("select UserID from AbBookerInfo where ImapMailbox is not null and ImapPassword is not null")->fetchFirstColumn();
    //
    //        foreach ($bookers as $booker) {
    //            $this->exec("app/console aw:booking:scan-mailbox {$booker} -vv --no-ansi", 0, false);
    //        }
    //    }
}
