<?php

declare(strict_types=1);

namespace AwardWallet\MainBundle\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20240722094256 extends AbstractMigration
{
    public function up(Schema $schema): void
    {
        $this->addSql('
            WITH MinIds AS (
                SELECT MIN(RAFlightSearchQueryID) AS MinId
                FROM RAFlightSearchQuery
                WHERE MileValueID IS NOT NULL
                GROUP BY MileValueID
            )
            DELETE FROM RAFlightSearchQuery
            WHERE MileValueID IS NOT NULL
            AND RAFlightSearchQueryID NOT IN (SELECT MinId FROM MinIds);
        ');
        $this->addSql('
            ALTER TABLE RAFlightSearchQuery
            ADD UNIQUE KEY RAFlightSearchQuery_MileValueID_unique (MileValueID);
        ');
    }

    public function down(Schema $schema): void
    {
        $this->addSql("
            ALTER TABLE RAFlightSearchQuery
            DROP INDEX RAFlightSearchQuery_MileValueID_unique;
        ");
    }
}
