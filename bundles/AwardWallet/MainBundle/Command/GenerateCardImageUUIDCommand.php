<?php

namespace AwardWallet\MainBundle\Command;

use AwardWallet\MainBundle\FrameworkExtension\Command;
use Doctrine\DBAL\Connection;
use Ramsey\Uuid\Uuid;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class GenerateCardImageUUIDCommand extends Command
{
    protected static $defaultName = 'card-image:generate-uuid';

    private Connection $connection;
    private Connection $unbufConnection;

    public function __construct(
        Connection $connection,
        Connection $unbufConnection
    ) {
        parent::__construct();
        $this->connection = $connection;
        $this->unbufConnection = $unbufConnection;
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $stmt = $this->unbufConnection->executeQuery("
            select 
                `CardImageID`
            from `CardImage`
            where
                UUID = ''
        ");

        $i = 0;

        while ($cardImageId = $stmt->fetchColumn(0)) {
            $uuid = Uuid::uuid4();

            if (++$i % 100 === 0) {
                $output->writeln("Processed {$i}...");
            }

            $this->connection->executeUpdate(
                'update `CardImage` set `UUID` = ? where `CardImageID` = ?',
                [$uuid->toString(), $cardImageId],
                [\PDO::PARAM_STR, \PDO::PARAM_INT]
            );
        }

        $output->writeln("Processed {$i} row(s)");
        $output->writeln("Done.");

        return 0;
    }
}
