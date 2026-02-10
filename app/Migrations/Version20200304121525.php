<?php

declare(strict_types=1);

namespace AwardWallet\MainBundle\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20200304121525 extends AbstractMigration
{
    public function up(Schema $schema): void
    {
        $this->addSql("
            ALTER TABLE `PurchaseStat`
                ADD COLUMN `BonusStartDate` DATETIME DEFAULT NULL COMMENT 'Время начала действия бонуса',
                ADD COLUMN `BonusEndDate` DATETIME DEFAULT NULL COMMENT 'Время окончания действия бонуса',
                ADD COLUMN `BonusDescription` varchar(255) DEFAULT NULL COMMENT 'Описание бонуса'
        ");
    }

    public function down(Schema $schema): void
    {
        $this->addSql("
            ALTER TABLE `PurchaseStat`
                DROP `BonusStartDate`,
                DROP `BonusEndDate`,
                DROP `BonusDescription`
        ");
    }
}
