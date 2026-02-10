<?php

namespace AwardWallet\MainBundle\Command;

use AwardWallet\MainBundle\FrameworkExtension\Command;
use Doctrine\DBAL\Connection;
use Psr\Log\LoggerInterface;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class RemoveAccountsCommand extends Command
{
    protected static $defaultName = 'aw:remove-accounts';

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

    protected function configure()
    {
        $this
            ->setDescription("Remove accounts from disabled providers, dry run by default")
            ->addArgument('providerCode', InputArgument::REQUIRED)
            ->addOption('remove', null, InputOption::VALUE_NONE)
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $logger = $this->logger;
        $schemaManager = new \TSchemaManager();
        $connection = $this->connection;

        $providerCode = $input->getArgument('providerCode');
        $providerState = $connection->fetchColumn("select State from Provider where Code = :code", ["code" => $providerCode]);

        if ($providerState != PROVIDER_DISABLED) {
            $logger->error("Cannot remove accounts from non-disabled provider");

            return 0;
        }
        $providerId = $connection->fetchColumn("select ProviderID from Provider where Code = :code", ["code" => $providerCode]);
        $logger->info("deleting accounts", ["providerCode" => $providerCode, "providerId" => $providerId]);
        $remove = $input->getOption('remove');

        if (!$remove) {
            $logger->info('dry run');
        }

        $q = $connection->executeQuery('select count(*) from Account where ProviderID = :providerId', ['providerId' => $providerId]);
        $number = $q->fetchColumn();

        $q = $connection->executeQuery('select * from Account where ProviderID = :providerId', ['providerId' => $providerId]);
        $deleted = 0;
        $accounts = 0;

        for ($i = 1; $accountId = $q->fetchColumn(); $i++) {
            $rows = $schemaManager->DeleteRow('Account', $accountId, $remove) ?: [];
            $logger->info(sprintf('%s/%s deleted', $i, $number), ['accountId' => $accountId]);
            ++$accounts;
            $deleted += count($rows);
        }

        $logger->info("done", ["accounts" => $accounts, "deleted records" => $deleted]);

        return 0;
    }
}
