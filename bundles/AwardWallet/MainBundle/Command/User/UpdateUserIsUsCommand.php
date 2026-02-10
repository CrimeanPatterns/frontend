<?php

namespace AwardWallet\MainBundle\Command\User;

use AwardWallet\MainBundle\Entity\EmailTemplate;
use AwardWallet\MainBundle\Service\EmailTemplate\DataProviderLoader;
use Doctrine\DBAL\Connection;
use Psr\Log\LoggerInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class UpdateUserIsUsCommand extends Command
{
    protected static $defaultName = 'aw:user:update-is-us';

    private LoggerInterface $logger;
    private Connection $connection;
    private DataProviderLoader $dataProviderLoader;

    public function __construct(
        LoggerInterface $logger,
        Connection $connection,
        DataProviderLoader $dataProviderLoader
    ) {
        $this->logger = $logger;
        $this->connection = $connection;
        $this->dataProviderLoader = $dataProviderLoader;
        parent::__construct();
    }

    public function configure(): void
    {
        $this->setDescription('Checking and updating User.IsUs field');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $currentIds = $this->getCurrentUserIds();
        $actualIds = $this->fetchActualUserIds();
        $retiredIds = array_values(array_diff($currentIds, $actualIds));
        $this->logger->info('US users, actual:' . count($actualIds) . ', current: ' . count($currentIds) . ', retired: ' . count($retiredIds));

        foreach (array_chunk($actualIds, 3000) as $userIds) {
            $this->connection->executeQuery('
                UPDATE Usr SET IsUs = 1
                WHERE UserID IN (' . implode(',', $userIds) . ')'
            );
        }

        foreach (array_chunk($retiredIds, 3000) as $userIds) {
            $this->connection->executeQuery('
                UPDATE Usr SET IsUs = 0
                WHERE UserID IN (' . implode(',', $userIds) . ')'
            );
        }

        $output->writeln('done.');

        return 0;
    }

    private function fetchActualUserIds(): array
    {
        $emailTemplate = (new EmailTemplate())->setDataProvider('DataUsUsers2InternalForIsUs');
        $dataProvider = $this->dataProviderLoader->getDataProviderByEmailTemplate($emailTemplate);
        $preparedSql = $dataProvider->getQuery()->getPreparedSql();

        return $this->connection->fetchFirstColumn(
            'SELECT outUs.UserID FROM (' . rtrim($preparedSql->getSql(), '; ') . ') AS outUs',
            $preparedSql->getParams(),
            $preparedSql->getTypes()
        );
    }

    private function getCurrentUserIds(): array
    {
        return $this->connection->fetchFirstColumn('
            SELECT UserID
            FROM Usr
            WHERE IsUs = 1
        ');
    }
}
