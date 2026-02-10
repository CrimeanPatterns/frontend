<?php

declare(strict_types=1);

namespace AwardWallet\MainBundle\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20190124081118 extends AbstractMigration
{
    public function up(Schema $schema): void
    {
        $this->addSql("
            CREATE TABLE `MerchantGroup` (
              `MerchantGroupID` int(11) NOT NULL AUTO_INCREMENT,
              `Name` varchar(250) NOT NULL COMMENT 'Имя группы мерчантов',
              `ClickURL` varchar(512) DEFAULT NULL COMMENT 'Ссылка на описание в блоге',
              PRIMARY KEY (`MerchantGroupID`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8;        
        ");

        $this->addSql("
            CREATE TABLE `MerchantGroupMerchant` (
              `MerchantGroupID` int(11) NOT NULL,
              `MerchantID` int(11) NOT NULL,
              PRIMARY KEY (`MerchantGroupID`, `MerchantID`),
              CONSTRAINT `MerchantGroupMerchant_ibfk_1` FOREIGN KEY (`MerchantGroupID`) REFERENCES `MerchantGroup` (`MerchantGroupID`) ON DELETE CASCADE,
              CONSTRAINT `MerchantGroupMerchant_ibfk_2` FOREIGN KEY (`MerchantID`) REFERENCES `Merchant` (`MerchantID`) ON DELETE CASCADE
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8 COMMENT 'Таблица связей мерчантов и группы мерчантов';        
        ");

        $this->addSql("
            CREATE TABLE `CreditCardMerchantGroup` (
              `CreditCardMerchantGroupID` int(11) NOT NULL AUTO_INCREMENT,
              `CreditCardID` int(11) NOT NULL,
              `MerchantGroupID` int(11) DEFAULT NULL,
              `Multiplier` decimal(3,1) NOT NULL COMMENT 'Мультипликатор = отношение полученных миль к потраченным $ в рамках транзакции',
              `StartDate` date DEFAULT NULL COMMENT 'Дата начала расчетного квартала',
              `Description` mediumtext COMMENT 'обьяснения как получить такой multiplier по такой группе мерчантов на такой карте',
              `SortIndex` int(4) NOT NULL DEFAULT '0',
              PRIMARY KEY (`CreditCardMerchantGroupID`),
              UNIQUE KEY `MerchantGroupID` (`MerchantGroupID`,`CreditCardID`,`StartDate`),
              CONSTRAINT `CCMG_ibfk_1` FOREIGN KEY (`CreditCardID`) REFERENCES `CreditCard` (`CreditCardID`) ON DELETE CASCADE,
              CONSTRAINT `CCMG_ibfk_2` FOREIGN KEY (`MerchantGroupID`) REFERENCES `MerchantGroup` (`MerchantGroupID`) ON DELETE CASCADE
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8;        
        ");
    }

    public function down(Schema $schema): void
    {
        $this->addSql("
            DROP TABLE `CreditCardMerchantGroup`;
            DROP TABLE `MerchantGroupMerchant`;
            DROP TABLE `MerchantGroup`;
        ");
    }
}
