<?php

namespace AwardWallet\MainBundle\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
class Version20161012104744 extends AbstractMigration
{
    public function up(Schema $schema): void
    {
        $this->addSql("
			ALTER TABLE `Cart` ADD `PurchaseToken` VARCHAR(1000)  NULL  DEFAULT NULL  COMMENT 'Необходим для андройда, получение инфы о платеже'  AFTER `IncomeTransactionID`;

            CREATE TABLE `InAppIOSReceipt` (
              `InAppIOSReceiptID` int(11) unsigned NOT NULL AUTO_INCREMENT,
              `UserID` int(11) NOT NULL COMMENT 'Чей рецепт',
              `Receipt` text NOT NULL COMMENT 'Рецепт',
              PRIMARY KEY (`InAppIOSReceiptID`),
              KEY `UserID` (`UserID`),
              CONSTRAINT `InAppIOSReceipt_ibfk_1` FOREIGN KEY (`UserID`) REFERENCES `Usr` (`UserID`) ON DELETE CASCADE
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8;
		");
    }

    public function down(Schema $schema): void
    {
        $this->addSql('DROP TABLE `InAppIOSReceipt`');
        $this->addSql('ALTER TABLE `Cart` DROP `PurchaseToken`;');
    }
}
