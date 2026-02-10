<?php

namespace AwardWallet\MainBundle\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
class Version20150304095630 extends AbstractMigration
{
    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE `MobileDevice` DROP KEY `idx_MobileDevice_Key`');
        $this->addSql('ALTER TABLE `MobileDevice` ADD UNIQUE KEY `idx_MobileDevice_Key_Type` (`DeviceKey`(255), `DeviceType`)');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE `MobileDevice` DROP KEY `idx_MobileDevice_Key_Type`');
        $this->addSql('ALTER TABLE `MobileDevice` ADD KEY `idx_MobileDevice_Key` (`DeviceKey`)');
    }
}
