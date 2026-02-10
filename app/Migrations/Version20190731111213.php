<?php

declare(strict_types=1);

namespace AwardWallet\MainBundle\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20190731111213 extends AbstractMigration
{
    public function up(Schema $schema): void
    {
        $this->addSql("ALTER TABLE `RewardsTransfer` ADD `RewardsType` TINYINT(2) NOT NULL DEFAULT '0' COMMENT 'RewardsTransfer::TYPE' AFTER `RewardsTransferID`");
        $this->addSql("ALTER TABLE `RewardsTransfer` ADD INDEX(`RewardsType`)");
    }

    public function down(Schema $schema): void
    {
        $this->addSql("ALTER TABLE `RewardsTransfer` DROP `RewardsType`");
    }
}
