<?php

declare(strict_types=1);

namespace AwardWallet\MainBundle\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20230603085707 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'set COLLATE utf8mb4_unicode_ci';
    }

    public function up(Schema $schema): void
    {
        // $this->addSql("SET FOREIGN_KEY_CHECKS=0");
        $this->connection->executeStatement("
            ALTER TABLE BusinessSubscription MODIFY COLUMN EndDate TIMESTAMP DEFAULT NULL
        ");
        $this->connection->executeStatement("
            ALTER TABLE CarQuery 
                MODIFY COLUMN CreationDate DATETIME DEFAULT CURRENT_TIMESTAMP NOT NULL,
                MODIFY COLUMN PickUpDate DATETIME DEFAULT CURRENT_TIMESTAMP NOT NULL,
                MODIFY COLUMN DropOffDate DATETIME DEFAULT CURRENT_TIMESTAMP NOT NULL;
        ");
        $this->connection->executeStatement("
            ALTER TABLE Deal 
                MODIFY COLUMN CreateDate DATETIME DEFAULT CURRENT_TIMESTAMP NOT NULL;
        ");
        $this->connection->executeStatement("
            ALTER TABLE FlightQuery 
                MODIFY COLUMN DepartDate DATETIME DEFAULT CURRENT_TIMESTAMP NOT NULL,
                MODIFY COLUMN ArriveDate DATETIME DEFAULT CURRENT_TIMESTAMP NOT NULL,
                MODIFY COLUMN CreationDate DATETIME DEFAULT CURRENT_TIMESTAMP NOT NULL;
        ");
        $this->connection->executeStatement("
            ALTER TABLE HotelQuery_DELETE 
                MODIFY COLUMN CreationDate DATETIME DEFAULT CURRENT_TIMESTAMP NOT NULL,
                MODIFY COLUMN CheckInDate DATETIME DEFAULT CURRENT_TIMESTAMP NOT NULL,
                MODIFY COLUMN CheckOutDate DATETIME DEFAULT CURRENT_TIMESTAMP NOT NULL;
        ");
        $this->connection->executeStatement("
            UPDATE OneCardPrinting SET PrintDate = NULL WHERE PrintDate < '0000-01-01 00:00:00';
        ");
        $this->connection->executeStatement("
            ALTER TABLE PartnerComment 
                MODIFY COLUMN CreationDate DATETIME DEFAULT CURRENT_TIMESTAMP NOT NULL;
        ");
        $this->connection->executeStatement("
            ALTER TABLE Poll 
                MODIFY COLUMN CreationDate DATETIME DEFAULT CURRENT_TIMESTAMP NOT NULL;
        ");
        $this->connection->executeStatement("
            ALTER TABLE Transaction 
                MODIFY COLUMN CreationDate DATETIME DEFAULT CURRENT_TIMESTAMP NOT NULL;
        ");

        $this->write('Processing TripSegment.ScheduledDepDate');
        $this->connection->executeStatement("
            UPDATE TripSegment SET ScheduledDepDate = DepDate WHERE ScheduledDepDate < '0000-01-01 00:00:00';
        ");

        $this->write('Processing TripSegment.ScheduledArrDate');
        $this->connection->executeStatement("
            UPDATE TripSegment SET ScheduledArrDate = ArrDate WHERE ScheduledArrDate < '0000-01-01 00:00:00';
        ");
        $this->connection->executeStatement("
            ALTER TABLE forums 
                MODIFY COLUMN vTime DATETIME DEFAULT CURRENT_TIMESTAMP NOT NULL;
        ");
        $this->connection->executeStatement("
            ALTER TABLE log 
                MODIFY COLUMN LogDate DATETIME DEFAULT CURRENT_TIMESTAMP NOT NULL;
        ");

        $tables = ["AAMembership"];

        foreach ($tables as $table) {
            if (in_array($table, ['RAFlight', 'MigrationVersions'])) {
                continue;
            }

            $this->write('Processing table ' . $table);
            $this->connection->executeStatement(
                sprintf(
                    'ALTER TABLE %s CONVERT TO CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci',
                    $table
                )
            );
        }
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs

    }
}
