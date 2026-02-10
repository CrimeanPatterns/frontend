<?php

declare(strict_types=1);

namespace AwardWallet\MainBundle\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20201201010101 extends AbstractMigration
{
    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE `TransferStat` ADD `CustomMessage` VARCHAR(255) NULL DEFAULT NULL AFTER `BonusPercentage`');
        $this->addSql("ALTER TABLE `TransferStat` CHANGE `BonusEndDate` `BonusEndDate` DATETIME NULL DEFAULT NULL COMMENT 'Время окончания действия бонуса (расчёт по времени относительно EST)'");
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE `TransferStat` DROP `CustomMessage`');
    }
}
