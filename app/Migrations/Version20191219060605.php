<?php

declare(strict_types=1);

namespace AwardWallet\MainBundle\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20191219060605 extends AbstractMigration
{
    public function up(Schema $schema): void
    {
        $this->addSql("
            ALTER TABLE `TransferStat`
                ADD COLUMN `BonusStartDate` DATETIME DEFAULT NULL COMMENT 'Время начала действия бонуса',
                ADD COLUMN `BonusEndDate` DATETIME DEFAULT NULL COMMENT 'Время окончания действия бонуса',
                ADD COLUMN `BonusPercentage` int(11) DEFAULT NULL COMMENT 'Размер бонуса в процентах'
        ");
    }

    public function down(Schema $schema): void
    {
        $this->addSql("
            ALTER TABLE `TransferStat`
                DROP `BonusStartDate`,
                DROP `BonusEndDate`,
                DROP `BonusPercentage`
        ");
    }
}
