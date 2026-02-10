<?php

namespace AwardWallet\MainBundle\Command;

use AwardWallet\MainBundle\FrameworkExtension\Command;
use AwardWallet\MainBundle\FrameworkExtension\Mailer\DataProvider\RewardsActivityProvider;
use AwardWallet\MainBundle\FrameworkExtension\Mailer\MailerCollection;
use Doctrine\DBAL\Connection;
use Psr\Log\LoggerInterface;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class RewardsActivityCommand extends Command
{
    protected static $defaultName = 'aw:email:recent';

    private LoggerInterface $logger;
    private Connection $connection;
    private RewardsActivityProvider $rewardsActivityProvider;
    private MailerCollection $mailerCollection;

    public function __construct(
        LoggerInterface $logger,
        Connection $connection,
        RewardsActivityProvider $rewardsActivityProvider,
        MailerCollection $mailerCollection
    ) {
        parent::__construct();
        $this->logger = $logger;
        $this->connection = $connection;
        $this->rewardsActivityProvider = $rewardsActivityProvider;
        $this->mailerCollection = $mailerCollection;
    }

    protected function configure()
    {
        $this
            ->setDescription('Mailing recent activity')
            ->addArgument('period', InputArgument::REQUIRED, 'period day|week|month')
            ->addOption('userId', 'u', InputOption::VALUE_REQUIRED | InputOption::VALUE_IS_ARRAY, 'filter by userId')
            ->addOption('startUser', 'x', InputOption::VALUE_REQUIRED, 'starting from this user')
            ->addOption('delay', 'd', InputOption::VALUE_REQUIRED, 'sleep delay after each message (seconds)')
            ->addOption('providerId', 'p', InputOption::VALUE_REQUIRED | InputOption::VALUE_IS_ARRAY, 'filter by providerId')
            ->addOption('startDate', 'r', InputOption::VALUE_REQUIRED, 'start date')
            ->addOption('testEmail', 'm', InputOption::VALUE_REQUIRED, 'send mail to this email instead of real address')
            ->addOption('testMode', 't', InputOption::VALUE_NONE, 'test mode, do not send anything, just log')
            ->addOption('limit', 'l', InputOption::VALUE_REQUIRED, 'limit of emails')
            ->addOption('packet', 'f', InputOption::VALUE_REQUIRED, 'user chunk size')
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $packetSize = $input->getOption('packet');

        if ((int) $packetSize) {
            $usersCount = (int) $this->connection
                ->executeQuery("select max(UserID) as MaxUserID from Usr ")
                ->fetch()['MaxUserID'];

            $segments = [];
            $counter = 1;

            while ($usersCount > $counter) {
                $segments[] = ['startUser' => $counter, 'endUser' => $counter + $packetSize - 1];
                $counter += $packetSize;
            }
        }

        // period
        $periodName = $input->getArgument('period');

        if (($period = array_search($periodName, RewardsActivityProvider::PERIOD)) === false) {
            throw new \Exception(sprintf("Unknown period: %s", $periodName));
        }

        $this->logger->info(sprintf("period: %s", $periodName));
        $this->rewardsActivityProvider->setPeriod($period);

        // filter by user
        if ($usersIds = $input->getOption("userId")) {
            $usersIds = array_map("intval", $usersIds);
            $this->logger->info(sprintf("filter by userId: [%s]", implode(", ", $usersIds)));
            $this->rewardsActivityProvider->setUsers($usersIds);
        }

        // from user
        if (!empty($startUser = $input->getOption("startUser"))) {
            $startUser = intval($startUser);
            $this->logger->info(sprintf("start user: %d", $startUser));
            $this->rewardsActivityProvider->setStartUser($startUser);
        }

        // delay
        if (!empty($delay = $input->getOption("delay"))) {
            $delay = intval($delay);
            $this->logger->info(sprintf("delay: %d", $delay));
            $this->rewardsActivityProvider->setDelay($delay);
        }

        // filter by provider
        if ($providerIds = $input->getOption("providerId")) {
            $providerIds = array_map("intval", $providerIds);
            $this->logger->info(sprintf("filter by providerId: [%s]", implode(", ", $providerIds)));
            $this->rewardsActivityProvider->setProviders($providerIds);
        }

        // start date
        if (!empty($startDate = $input->getOption("startDate"))) {
            $startDate = strtotime($startDate);

            if ($startDate !== false) {
                $startDate = new \DateTime('@' . $this->roundDate($startDate));
            }
        }

        if (!isset($startDate) || !($startDate instanceof \DateTime)) {
            $startDate = new \DateTime('@' . $this->roundDate(strtotime("-1 " . $periodName, time())));
        }
        $this->logger->info(sprintf("start date: %s", $startDate->format("m/d/Y H:i:s")));
        $this->rewardsActivityProvider->setStartDate($startDate);

        // end date
        $endDate = new \DateTime('@' . ($this->roundDate(strtotime("+1 " . $periodName, $startDate->getTimestamp())) - 1));
        $this->logger->info(sprintf("end date: %s", $endDate->format("m/d/Y H:i:s")));
        $this->rewardsActivityProvider->setEndDate($endDate);

        // test email
        if (!empty($testEmail = $input->getOption("testEmail"))) {
            $this->logger->info(sprintf("test email: %s", $testEmail));
            $this->rewardsActivityProvider->setTestEmail($testEmail);
        }

        // test mode
        $testMode = $input->getOption("testMode");
        $this->logger->info(sprintf("test mode: %s", $testMode ? 'true' : 'false'));
        $this->rewardsActivityProvider->setTestMode($testMode);

        // limit
        if ($limit = $input->getOption("limit")) {
            $limit = intval($limit);
            $this->logger->info(sprintf("limit of emails: %d", $limit));
        }

        $collectionMailer = $this->mailerCollection;

        // Mailer
        if (isset($segments)) {
            $processed = 0;
            $sent = 0;

            foreach ($segments as $segment) {
                $this->rewardsActivityProvider->setStartUser($segment['startUser']);
                $this->rewardsActivityProvider->setEndUser($segment['endUser']);
                $this->rewardsActivityProvider->reset();
                $collectionMailer->setDataProvider($this->rewardsActivityProvider);
                $output->writeln(sprintf("checking users from %d to %d", $segment['startUser'], $segment['endUser']));
                $collectionMailer->send();
                $output->writeln(sprintf("checked users from %d to %d, found %d eligible users, sent %d emails", $segment['startUser'], $segment['endUser'], $this->rewardsActivityProvider->getProcessed(), $collectionMailer->getTotalSends()));
                $sent += $collectionMailer->getTotalSends();
                $processed += $this->rewardsActivityProvider->getProcessed();
            }
            $output->writeln(sprintf("processed %d users, sent %d emails total", $processed, $sent));
        } else {
            $collectionMailer->setDataProvider($this->rewardsActivityProvider);

            if (!empty($limit)) {
                $collectionMailer->setLimit($limit);
            }
            $collectionMailer->send();
            $output->writeln(sprintf("sent %d emails", $collectionMailer->getTotalSends()));
        }

        $output->writeln("done.");

        return 0;
    }

    private function roundDate($d)
    {
        $date = getdate($d);

        return mktime($date['hours'], 0, 0, $date['mon'], $date['mday'], $date['year']);
    }
}
