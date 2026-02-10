<?php

namespace AwardWallet\MainBundle\Command\Fix;

use AwardWallet\MainBundle\FrameworkExtension\Command;
use Doctrine\DBAL\Connection;
use Psr\Log\LoggerInterface;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class FixSharingCommand extends Command
{
    protected static $defaultName = 'aw:fix-sharing';

    private LoggerInterface $logger;
    private Connection $connection;

    public function __construct(
        LoggerInterface $logger,
        Connection $connection
    ) {
        parent::__construct();
        $this->logger = $logger;
        $this->connection = $connection;
    }

    public function configure()
    {
        $this
            ->setDescription('Fix invalid UserAgentID in Account table');
    }

    public function execute(InputInterface $input, OutputInterface $output): int
    {
        $connection = $this->connection;
        $logger = $this->logger;

        $logger->info("searching for accounts with UserAgentID pointing to not family member");
        $invalids = $connection->executeQuery("
            select a.AccountID, a.Login, a.UserAgentID, a.UserID as AccountUser, ua.AgentID as FamilyUser, ua.ClientID 
            from Account a
            join UserAgent ua on a.UserAgentID = ua.UserAgentID
            where a.UserID <> ua.AgentID
        ")->fetchAll(\PDO::FETCH_ASSOC);

        foreach ($invalids as $invalid) {
            $logger->warning("correcting", $invalid);
            $connection->executeUpdate("update Account set UserAgentID = null where AccountID = :accountId", ["accountId" => $invalid['AccountID']]);
        }

        $logger->info("corrected " . count($invalids) . " records");

        return 0;
    }
}
