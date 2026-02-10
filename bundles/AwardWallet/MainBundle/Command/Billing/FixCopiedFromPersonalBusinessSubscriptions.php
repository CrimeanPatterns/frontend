<?php

namespace AwardWallet\MainBundle\Command\Billing;

use AwardWallet\MainBundle\Entity\Useragent;
use Doctrine\DBAL\Connection;
use Psr\Log\LoggerInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class FixCopiedFromPersonalBusinessSubscriptions extends Command
{
    protected static $defaultName = 'aw:fix-copied-from-personal-business-subscriptions';
    private LoggerInterface $logger;
    private Connection $connection;

    public function __construct(LoggerInterface $logger, Connection $connection)
    {
        parent::__construct();

        $this->logger = $logger;
        $this->connection = $connection;
    }

    public function configure()
    {
        $this
            ->addOption('fix', null, InputOption::VALUE_NONE)
            ->addOption('backup-file', null, InputOption::VALUE_REQUIRED)
        ;
    }

    public function execute(InputInterface $input, OutputInterface $output): int
    {
        if ($input->getOption('fix') && !$input->getOption('backup-file')) {
            throw new \Exception("backup-file required in fix mode");
        }

        if ($input->getOption('backup-file')) {
            $f = fopen($input->getOption('backup-file'), "wb");
        }

        $users = $this->connection->executeQuery("
            select 
                bu.UserID as BusinessID,
                au.UserID as AdminID,
                bu.Subscription,
                bu.PayPalRecurringProfileID
            from
                Usr bu
                join UserAgent ua on ua.ClientID = bu.UserID and ua.AccessLevel = " . Useragent::ACCESS_ADMIN . "
                join Usr au on ua.AgentID = au.UserID    
            where
                bu.Subscription = au.Subscription
                and bu.Subscription is not null
                and bu.PayPalRecurringProfileID = au.PayPalRecurringProfileID
                and bu.PayPalRecurringProfileID is not null
            order by
                bu.UserID
        ")->fetchAllAssociative();

        foreach ($users as $user) {
            $this->logger->info(json_encode($user));

            if ($input->getOption('backup-file')) {
                fputs($f, json_encode($user) . "\n");
            }

            if ($input->getOption('fix')) {
                $this->connection->executeStatement("update Usr set Subscription = null, PayPalRecurringProfileID = null where UserID = ?", [$user['BusinessID']]);
            }
        }

        if ($input->getOption('backup-file')) {
            fclose($f);
        }

        return 0;
    }
}
