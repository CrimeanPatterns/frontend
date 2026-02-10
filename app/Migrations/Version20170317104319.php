<?php

namespace AwardWallet\MainBundle\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
class Version20170317104319 extends AbstractMigration
{
    public function up(Schema $schema): void
    {
        $this->addSql("
            ALTER TABLE `AccountHistory` 
            ADD COLUMN `Amount` DECIMAL(10,2) NULL comment 'Потраченная сумма в валюте',
            ADD COLUMN `AmountBalance` DECIMAL(10,2) NULL comment 'Баланс в валюте',
            ADD COLUMN `MilesBalance` DECIMAL(10,2) NULL comment 'Баланс в милях',
            ADD COLUMN `CurrencyID` INT UNSIGNED NULL DEFAULT NULL comment 'Валюта операции',
            ADD COLUMN `Category` VARCHAR(250) NULL comment 'Категория транзакции, типа Earned, Used',
            ADD CONSTRAINT `FK_AccountHistory_Currency` FOREIGN KEY (`CurrencyID`) REFERENCES `Currency` (`CurrencyID`) ON DELETE CASCADE
        ");
    }

    public function down(Schema $schema): void
    {
        $this->addSql("
            ALTER TABLE `AccountHistory` 
            DROP FOREIGN KEY `FK_AccountHistory_Currency`,
            DROP COLUMN `Amount`,
            DROP COLUMN `AmountBalance`,
            DROP COLUMN `MilesBalance`,
            DROP COLUMN `CurrencyID`,
            DROP COLUMN `Category`
        ");
    }
}
