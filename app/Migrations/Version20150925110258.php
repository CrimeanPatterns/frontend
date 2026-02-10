<?php

namespace AwardWallet\MainBundle\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
class Version20150925110258 extends AbstractMigration
{
    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE `MobileDevice` ADD `AppVersion` VARCHAR(16) DEFAULT NULL COMMENT \'Версия приложения на устройстве\';');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE `MobileDevice` DROP `AppVersion`');
    }
}
