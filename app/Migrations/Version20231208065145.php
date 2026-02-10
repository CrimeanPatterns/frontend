<?php

declare(strict_types=1);

namespace AwardWallet\MainBundle\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20231208065145 extends AbstractMigration
{
    public function up(Schema $schema): void
    {
        $this->addSql("
            ALTER TABLE RAFlightSearchRoute
            ADD COLUMN Flag TINYINT(1) NOT NULL DEFAULT 0 COMMENT 'Флаг перелета' AFTER Archived,
            ADD INDEX RAFlightSearchRoute_Flag (Flag)
        ");
    }

    public function down(Schema $schema): void
    {
        $this->addSql("
            ALTER TABLE RAFlightSearchRoute
            DROP INDEX RAFlightSearchRoute_Flag,
            DROP COLUMN Flag
        ");
    }
}
