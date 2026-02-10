<?php

namespace AwardWallet\MainBundle\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
class Version20170914114655 extends AbstractMigration
{
    public function up(Schema $schema): void
    {
        $this->addSql("
            CREATE TABLE `Merchant` (
              `MerchantID` int(11) NOT NULL AUTO_INCREMENT,
              `Name` varchar(250) NOT NULL,
              `Patterns` mediumtext COMMENT 'Регэкспы для определения магазина, алиасы, один регэксп на строку',
              PRIMARY KEY (`MerchantID`),
              UNIQUE KEY (`Name`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8 COMMENT='Магазины, участвующие в транзакциях по картам';        
        ");

        $this->addSql("
            CREATE TABLE `MerchantAlias` (
              `MerchantAliasID` int(11) NOT NULL AUTO_INCREMENT,
              `MerchantID` int(11) NOT NULL,
              `Alias` varchar(250) NOT NULL,
              PRIMARY KEY (`MerchantAliasID`),
              UNIQUE KEY (`Alias`),
              CONSTRAINT `MerchantAlias_ibfk_1` FOREIGN KEY (`MerchantID`) REFERENCES `Merchant` (`MerchantID`) ON DELETE CASCADE
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8 COMMENT='Алиасы магазинов, участвующих в транзакциях по картам'; 
       ");

        $this->addSql("
            CREATE TABLE `CreditCard` (
              `CreditCardID` int(11) NOT NULL AUTO_INCREMENT,
              `ProviderID` int(11) NOT NULL,
              `Name` varchar(250) NOT NULL COMMENT 'Красивое название карты, ищем не по нему, ищем по алиасам',
              `Patterns` mediumtext COMMENT 'Регэкспы для определения карты, алиасы, один регэксп на строку',
              `MatchingOrder` int(11) NOT NULL COMMENT 'Порядок для матчинга при заполнении поля CreditCardID в таблице SubAccount',
              PRIMARY KEY (`CreditCardID`),
              UNIQUE KEY (`ProviderID`,`Name`),
              INDEX (`ProviderID`, `MatchingOrder`),
              CONSTRAINT `CreditCard_ibfk_1` FOREIGN KEY (`ProviderID`) REFERENCES `Provider` (`ProviderID`) ON DELETE CASCADE
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8 COMMENT='Кредитные карты провайдеров';
       ");

        $this->addSql("
            CREATE TABLE `CreditCardMultiplier` (
              `CreditCardMultiplierID` int(11) NOT NULL AUTO_INCREMENT,
              `CreditCardID` int(11) NOT NULL,
              `MerchantID` int(11) NOT NULL,
              `StartDate` date NOT NULL COMMENT 'Дата начала расчетного квартала',
              `Multiplier` decimal(3,1) NOT NULL COMMENT 'Мультипликатор = отношение полученных миль к потраченным $ в рамках транзакции',
              `Transactions` int(11) NOT NULL COMMENT 'Число транзакций с таким множителем',
              PRIMARY KEY (`CreditCardMultiplierID`),
              UNIQUE KEY (`CreditCardID`,`MerchantID`,`StartDate`,`Multiplier`),
              KEY `MerchantID` (`MerchantID`),
              CONSTRAINT `CreditCardMultiplier_ibfk_1` FOREIGN KEY (`CreditCardID`) REFERENCES `CreditCard` (`CreditCardID`) ON DELETE CASCADE,
              CONSTRAINT `CreditCardMultiplier_ibfk_2` FOREIGN KEY (`MerchantID`) REFERENCES `Merchant` (`MerchantID`) ON DELETE CASCADE
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8 COMMENT='Сырые результаты аналитики истории';
       ");

        $this->addSql("
            CREATE TABLE `EarningPotential` (
              `EarningPotentialID` int(11) NOT NULL AUTO_INCREMENT,
              `CreditCardID` int(11) NOT NULL,
              `MerchantID` int(11) NOT NULL,
              `StartDate` date NOT NULL COMMENT 'Дата начала расчетного квартала',
              `Potential` decimal(3,1) NOT NULL,
              PRIMARY KEY (`EarningPotentialID`),
              UNIQUE KEY (`CreditCardID`,`MerchantID`,`StartDate`),
              KEY `MerchantID` (`MerchantID`),
              CONSTRAINT `EarningPotential_ibfk_1` FOREIGN KEY (`CreditCardID`) REFERENCES `CreditCard` (`CreditCardID`) ON DELETE CASCADE,
              CONSTRAINT `EarningPotential_ibfk_2` FOREIGN KEY (`MerchantID`) REFERENCES `Merchant` (`MerchantID`) ON DELETE CASCADE
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8 COMMENT='Потенциал заработка, выбирается только один для сочетания карта+мерчант';
       ");
    }

    public function down(Schema $schema): void
    {
        $this->addSql("DROP TABLE `EarningPotential`");
        $this->addSql("DROP TABLE `CreditCardMultiplier`");
        $this->addSql("DROP TABLE `CreditCard`");
        $this->addSql("DROP TABLE `MerchantAlias`");
        $this->addSql("DROP TABLE `Merchant`");
    }
}
