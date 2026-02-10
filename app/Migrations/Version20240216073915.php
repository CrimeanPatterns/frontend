<?php

declare(strict_types=1);

namespace AwardWallet\MainBundle\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20240216073915 extends AbstractMigration
{
    public function up(Schema $schema): void
    {
        $this->addSql("
            ALTER TABLE RAFlightSearchQuery
            ADD COLUMN MaxTotalDuration DECIMAL(5, 2) NULL COMMENT 'Максимальная длительность всех перелетов включая пересадки' AFTER FirstMilesLimit,
            ADD COLUMN MaxSingleLayoverDuration DECIMAL(5, 2) NULL COMMENT 'Максимальная длительность одной пересадки' AFTER MaxTotalDuration,
            ADD COLUMN MaxTotalLayoverDuration DECIMAL(5, 2) NULL COMMENT 'Максимальная длительность всех пересадок' AFTER MaxSingleLayoverDuration,
            ADD COLUMN MaxStops INT NULL COMMENT 'Максимальное количество пересадок' AFTER MaxTotalLayoverDuration;
        ");
    }

    public function down(Schema $schema): void
    {
        $this->addSql("
            ALTER TABLE RAFlightSearchQuery
            DROP COLUMN MaxTotalDuration,
            DROP COLUMN MaxSingleLayoverDuration,
            DROP COLUMN MaxTotalLayoverDuration,
            DROP COLUMN MaxStops;
        ");
    }
}
