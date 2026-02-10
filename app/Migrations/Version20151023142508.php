<?php

namespace AwardWallet\MainBundle\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

class Version20151023142508 extends AbstractMigration
{
    public function up(Schema $schema): void
    {
        $this->addSql('
            CREATE TABLE `RetailBenefitsCashback` (
                  `RetailBenefitsCashbackID` INT NOT NULL,
                  `UserID` INT NOT NULL,
                  `SettlementType` VARCHAR(11) NOT NULL,
                  `PurchaseAmount` FLOAT NULL,
                  `PurchaseDate` DATETIME NULL,
                  `CashbackAmount` FLOAT NULL,
                  `PaymentAmount` FLOAT NULL,
                  `PaymentDate` DATETIME NULL,
                  `AvailableDate` DATETIME NULL,
                  `Status` VARCHAR(10) NOT NULL,
                  `MerchantID` INT NOT NULL,
                  PRIMARY KEY (`RetailBenefitsCashbackID`),
                  INDEX `RetailBenefitsCashback_UserID_idx` (`UserID` ASC),
                  INDEX `RetailBenefitsCashback_MerchantID_idx` (`MerchantID` ASC),
                  INDEX `RetailBenefitsCashback_Status_idx` (`Status` ASC),
                  CONSTRAINT `RetailBenefitsCashbackUserID`
                      FOREIGN KEY (`UserID`)
                      REFERENCES `Usr` (`UserID`)
                      ON DELETE CASCADE
                      ON UPDATE CASCADE
            )
            engine=InnoDB comment "Данные о Retail Benefits кэшбэк транзакциях"
        ');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('DROP TABLE `RetailBenefitsCashback`');
    }
}
