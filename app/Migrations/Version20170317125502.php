<?php

namespace AwardWallet\MainBundle\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
class Version20170317125502 extends AbstractMigration
{
    public function up(Schema $schema): void
    {
        $this->addSql(
            "CREATE TABLE `CustomLoyaltyProperty` (
                `CustomLoyaltyPropertyID` int(11) NOT NULL AUTO_INCREMENT,
                
                `AccountID` int(11) DEFAULT NULL COMMENT 'Аккаунт',
                `SubAccountID` int(11) DEFAULT NULL COMMENT 'Субаккаунт',
                `ProviderCouponID` int(11) DEFAULT NULL COMMENT 'Купон',
                
                `Name` VARCHAR(128) NOT NULL COMMENT 'Имя свойства',
                `Value` VARCHAR(512) NOT NULL COMMENT 'Значение свойства',
                
                PRIMARY KEY (`CustomLoyaltyPropertyID`),
                UNIQUE KEY (`AccountID`, `Name`),
                UNIQUE KEY (`SubAccountID`, `Name`),
                UNIQUE KEY (`ProviderCouponID`, `Name`),
                
                CONSTRAINT `CustomLoyaltyProperty_Account_AccountID` FOREIGN KEY (`AccountID`) REFERENCES `Account` (`AccountID`) ON DELETE CASCADE,
                CONSTRAINT `CustomLoyaltyProperty_SubAccount_AccountID` FOREIGN KEY (`SubAccountID`) REFERENCES `SubAccount` (`SubAccountID`) ON DELETE CASCADE,
                CONSTRAINT `CustomLoyaltyProperty_ProviderCoupon_ProviderCouponID` FOREIGN KEY (`ProviderCouponID`) REFERENCES `ProviderCoupon` (`ProviderCouponID`) ON DELETE CASCADE 
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8"
        );
    }

    public function down(Schema $schema): void
    {
        $this->addSql('DROP TABLE `CustomLoyaltyProperty`');
    }
}
