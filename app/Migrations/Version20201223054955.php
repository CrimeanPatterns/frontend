<?php

declare(strict_types=1);

namespace AwardWallet\MainBundle\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20201223054955 extends AbstractMigration
{
    public function up(Schema $schema): void
    {
        $this->addSql("
            ALTER TABLE AirCode 
            ADD TimeZoneLocation VARCHAR(64) NOT NULL DEFAULT 'UTC' COMMENT 'Таймзона, прим. Asia/Shanghai' AFTER TimeZoneID
        ;");

        $this->addSql("
            ALTER TABLE GeoTag 
            ADD TimeZoneLocation VARCHAR(64) NOT NULL DEFAULT 'UTC' COMMENT 'Таймзона, прим. Asia/Shanghai' AFTER TimeZoneID
        ;");
    }

    public function down(Schema $schema): void
    {
        $this->addSql("ALTER TABLE AirCode DROP COLUMN TimeZoneLocation;");
        $this->addSql("ALTER TABLE GeoTag DROP COLUMN TimeZoneLocation;");
    }
}
