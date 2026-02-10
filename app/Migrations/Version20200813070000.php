<?php

declare(strict_types=1);

namespace AwardWallet\MainBundle\Migrations;

use Doctrine\DBAL\FetchMode;
use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;
use Psr\Log\LoggerInterface;
use Symfony\Component\DependencyInjection\ContainerAwareInterface;
use Symfony\Component\DependencyInjection\ContainerAwareTrait;

class Version20200813070000 extends AbstractMigration implements ContainerAwareInterface
{
    use ContainerAwareTrait;

    private const CORRECT_AIRLINE_ID = 404; // JetBlue Airways
    private const WRONG_AIRLINE_ID = 1028; // Tigerair Taiwan
    private const BATCH_SIZE = 5000;

    /** @var LoggerInterface */
    private $logger;

    public function up(Schema $schema): void
    {
        $this->logger = $this->container->get('logger');

        $sqlSelect = '
            SELECT
                DISTINCT t.TripID
            FROM TripSegment ts
            JOIN Trip t ON (t.TripID = ts.TripID)
            WHERE
                    ts.AirlineID = ' . self::CORRECT_AIRLINE_ID . '
                AND t.AirlineID = ' . self::WRONG_AIRLINE_ID . '
            LIMIT ' . self::BATCH_SIZE . '
        ';

        $sqlUpdate = 'UPDATE Trip SET AirlineID = ' . self::CORRECT_AIRLINE_ID . ' WHERE TripID IN(:tripIds)';

        $this->info('Fix TripID by AirlineID JetBlue Airways Tigerair Taiwan');
        $totals = 0;

        do {
            $ids = $this->connection->executeQuery($sqlSelect)->fetchAll(FetchMode::COLUMN);
            $affectedRows = $this->connection->executeUpdate(
                $sqlUpdate,
                ['tripIds' => $ids],
                ['tripIds' => $this->connection::PARAM_INT_ARRAY]
            );
            $totals += $affectedRows;

            $this->info('Found wrong TripID: ' . implode(',', $ids));
        } while ($affectedRows > 0);

        $this->info('Updated ' . $totals . ' Trip.TripID rows');
        $this->write('done');
    }

    public function down(Schema $schema): void
    {
    }

    private function info($message): void
    {
        $this->write($message);
        $this->logger->info($message);
    }
}
