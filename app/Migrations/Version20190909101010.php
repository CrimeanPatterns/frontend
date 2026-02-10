<?php

declare(strict_types=1);

namespace AwardWallet\MainBundle\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20190909101010 extends AbstractMigration
{
    public function up(Schema $schema): void
    {
        $this->addSql('DELETE FROM `RewardsTransfer` WHERE RewardsType IN (1, 2)');
        $this->addSql('ALTER TABLE `RewardsTransfer` DROP INDEX `RewardsType`');
        $this->addSql('ALTER TABLE `RewardsTransfer` DROP `RewardsType`');
    }

    public function down(Schema $schema): void
    {
        $this->addSql("ALTER TABLE `RewardsTransfer` ADD `RewardsType` TINYINT(2) NOT NULL DEFAULT '0' COMMENT 'RewardsTransfer::TYPE' AFTER `RewardsTransferID`");
        $this->addSql("ALTER TABLE `RewardsTransfer` ADD INDEX(`RewardsType`)");
    }
}
