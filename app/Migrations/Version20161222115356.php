<?php

namespace AwardWallet\MainBundle\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
class Version20161222115356 extends AbstractMigration
{
    public function up(Schema $schema): void
    {
        $this->addSql("ALTER TABLE Usr ADD COLUMN SplashAdsDisabled tinyint NOT NULL DEFAULT 0");
        $this->addSql("ALTER TABLE Usr ADD COLUMN ListAdsDisabled tinyint NOT NULL DEFAULT 0");
    }

    public function down(Schema $schema): void
    {
        $this->addSql("ALTER TABLE Usr DROP COLUMN SplashAdsDisabled");
        $this->addSql("ALTER TABLE Usr DROP COLUMN ListAdsDisabled");
    }
}
