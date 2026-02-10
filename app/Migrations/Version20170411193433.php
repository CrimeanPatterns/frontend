<?php

namespace AwardWallet\MainBundle\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
class Version20170411193433 extends AbstractMigration
{
    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE `Usr` ADD `LinkAdsDisabled` TINYINT(1) NOT NULL DEFAULT 0 AFTER `SplashAdsDisabled`');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE `Usr` DROP `LinkAdsDisabled`');
    }
}
