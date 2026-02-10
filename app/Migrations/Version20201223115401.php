<?php

declare(strict_types=1);

namespace AwardWallet\MainBundle\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20201223115401 extends AbstractMigration
{
    public function up(Schema $schema): void
    {
        $this->addSql("
            ALTER TABLE StationCode 
            MODIFY COLUMN TimeZoneLocation VARCHAR(64) NOT NULL DEFAULT 'UTC' COMMENT 'Таймзона, прим. Asia/Shanghai' AFTER Lng
        ;");
    }

    public function down(Schema $schema): void
    {
        $this->addSql("ALTER TABLE StationCode MODIFY COLUMN TimeZoneLocation VARCHAR(64) NULL AFTER Lng;");
    }
}
