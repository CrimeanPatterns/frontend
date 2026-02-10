<?php

declare(strict_types=1);

namespace AwardWallet\MainBundle\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20190426111111 extends AbstractMigration
{
    public function up(Schema $schema): void
    {
        $this->addSql("
            CREATE TABLE `QsCreditCard` (
              `QsCreditCardID` int(11) NOT NULL AUTO_INCREMENT,
              `QsCardInternalKey` int(11) UNSIGNED DEFAULT NULL COMMENT 'QuinStreet CreditCardID',
              `CardName` varchar(255) NOT NULL DEFAULT '',
              `BonusMilesFull` TEXT NULL DEFAULT NULL,
              `Slug` varchar(64) DEFAULT NULL,
              `AwCreditCardID` int(11) DEFAULT NULL COMMENT 'CreditCard.CreditCardID',
              `IsManual` tinyint(1) NOT NULL DEFAULT '0' COMMENT 'Источник данных карты QuinStreet или добавлена вручную',
              `UpdateDate` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
              PRIMARY KEY (`QsCreditCardID`),
              UNIQUE KEY `QsCardInternalKey` (`QsCardInternalKey`),
              CONSTRAINT `fkAwCreditCardID` FOREIGN KEY (`AwCreditCardID`) REFERENCES `CreditCard` (`CreditCardID`) ON DELETE SET NULL ON UPDATE CASCADE
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8;
        ");

        $this->addSql("
            CREATE TABLE `QsCreditCardHistory` (
              `QsCreditCardHistoryID` int(11) NOT NULL AUTO_INCREMENT,
              `QsCreditCardID` int(11) NOT NULL,
              `CardName` varchar(255) NOT NULL,
              `BonusMilesFull` TEXT NULL DEFAULT NULL,
              `CreationDate` datetime NOT NULL,
              PRIMARY KEY (`QsCreditCardHistoryID`),
              KEY `fkQsCreditCardID` (`QsCreditCardID`),
              CONSTRAINT `fkHQsCreditCardID` FOREIGN KEY (`QsCreditCardID`) REFERENCES `QsCreditCard` (`QsCreditCardID`) ON DELETE CASCADE ON UPDATE CASCADE
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8;
        ");

        $this->addSql("
            CREATE TABLE `QsTransaction` (
              `QsTransactionID` int(11) NOT NULL AUTO_INCREMENT,
              `QsCreditCardID` int(11) DEFAULT NULL,
              `TransactionDate` date NOT NULL,
              `Account` tinyint(3) DEFAULT NULL,
              `Card` varchar(255) DEFAULT NULL,
              `Source` varchar(64) DEFAULT NULL,
              `Exit` varchar(64) DEFAULT NULL,
              `BlogPostID` bigint(20) DEFAULT NULL,
              `MID` varchar(64) DEFAULT NULL,
              `CID` varchar(64) DEFAULT NULL,
              `RefCode` varchar(16) DEFAULT NULL,
              `UserID` int(11) DEFAULT NULL,
              `Clicks` int(10) NOT NULL DEFAULT '0',
              `Earnings` decimal(10,2) NOT NULL DEFAULT '0.00',
              `CPC` decimal(10,2) NOT NULL DEFAULT '0.00',
              `Approvals` smallint(5) UNSIGNED NOT NULL DEFAULT '0',
              `RawAccount` varchar(255) DEFAULT NULL,
              `RawVar1` varchar(255) DEFAULT NULL,
              `Hash` varchar(40) NOT NULL,
              `CreationDate` datetime NOT NULL,
              PRIMARY KEY (`QsTransactionID`),
              UNIQUE KEY `Hash` (`Hash`),
              KEY `TransactionDate` (`TransactionDate`),
              CONSTRAINT `fkQsCreditCardID` FOREIGN KEY (`QsCreditCardID`) REFERENCES `QsCreditCard` (`QsCreditCardID`) ON DELETE SET NULL ON UPDATE CASCADE
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8;
        ");
    }

    public function down(Schema $schema): void
    {
    }
}
