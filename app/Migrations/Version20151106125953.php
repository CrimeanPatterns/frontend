<?php

namespace AwardWallet\MainBundle\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

class Version20151106125953 extends AbstractMigration
{
    public function up(Schema $schema): void
    {
        $this->addSql('
			CREATE TABLE `RetailBenefitsLink` (
				`RetailBenefitsLinkID` INT NOT NULL,
				`ClickedDate` DATETIME NULL NOT NULL,
				`ConvertedDate` DATETIME NULL,
				`CustomIdentifier` VARCHAR(100) NULL,
				`IpAddress` VARCHAR(15) NOT NULL,
				`MerchantID` INT NOT NULL,
				`OS` VARCHAR(100) NOT NULL,
				`Platform` VARCHAR(100) NULL,
				`ProgramCommissionAmount` FLOAT NOT NULL,
				`ReferrerCommissionAmount` FLOAT NOT NULL,
				`SaleAmount` FLOAT NOT NULL,
				`ShopperCommissionAmount` FLOAT NOT NULL,
				`UserAgent` VARCHAR(200) NULL,
				`UserID` INT NOT NULL,
				PRIMARY KEY (`RetailBenefitsLinkID`),
				INDEX `RetailBenefitsLink_UserID_idx` (`UserID` ASC),
				INDEX `RetailBenefitsLink_MerchantID_idx` (`MerchantID` ASC),
				CONSTRAINT `RetailBenefitsLinkUserID`
					FOREIGN KEY (`UserID`)
					REFERENCES `Usr` (`UserID`)
					ON DELETE CASCADE
					ON UPDATE CASCADE
			)
			engine=InnoDB comment "Данные о Retail Benefits реферальных ссылках"
		');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('DROP TABLE `RetailBenefitsLink`');
    }
}
