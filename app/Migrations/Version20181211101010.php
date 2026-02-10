<?php

declare(strict_types=1);

namespace AwardWallet\MainBundle\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20181211101010 extends AbstractMigration
{
    public function up(Schema $schema): void
    {
        $this->addSql("
            CREATE TABLE `BalanceWatchCreditsTransaction` (
                `BalanceWatchCreditsTransactionID` int(11) unsigned NOT NULL AUTO_INCREMENT,
                `UserID` int(11) NOT NULL,
                `AccountID` int(11) DEFAULT NULL,
                `TransactionType` smallint(5) NOT NULL COMMENT 'BalanceWatchCreditsTransaction::TRANSACTION_TYPE',
                `Amount` int(11) NOT NULL COMMENT 'Сумма транзакции',
                `Balance` int(11) NOT NULL COMMENT 'Баланс после совершения транзакции',
                `CreationDate` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
                PRIMARY KEY (`BalanceWatchCreditsTransactionID`),
                KEY `fk_Account_Id` (`AccountID`),
                KEY `fk_User_Id` (`UserID`),
                CONSTRAINT `fkbw_Account_Id` FOREIGN KEY (`AccountID`) REFERENCES `Account` (`AccountID`) ON DELETE SET NULL ON UPDATE CASCADE,
                CONSTRAINT `fkbw_User_Id` FOREIGN KEY (`UserID`) REFERENCES `Usr` (`UserID`) ON DELETE CASCADE ON UPDATE CASCADE
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8;
        ");
        $this->addSql("
            CREATE TABLE `BalanceWatch` (
              `BalanceWatchID` int(11) UNSIGNED NOT NULL AUTO_INCREMENT,
              `AccountID` int(11) DEFAULT NULL,
              `PayerUserID` int(11) DEFAULT NULL COMMENT 'Устанавливается если включает не владелец аккаунта',
              `PointsSource` tinyint(1) DEFAULT NULL  COMMENT 'Метод получения - transfer / purchase',
              `TransferFromProviderID` int(11) UNSIGNED DEFAULT NULL COMMENT 'Источник трансфера',
              `ExpectedPoints` decimal(18,2) DEFAULT NULL COMMENT 'Ожидаемое кол-во',
              `TransferRequestDate` datetime DEFAULT NULL COMMENT 'Примерное время запроса на получеие',
              `StopReason` tinyint(3) DEFAULT NULL,
              `StopDate` datetime DEFAULT NULL,
              `CreationDate` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
              PRIMARY KEY (`BalanceWatchID`),
              CONSTRAINT `fk_AccountId` FOREIGN KEY (`AccountID`) REFERENCES `Account` (`AccountID`) ON DELETE SET NULL ON UPDATE CASCADE,
              CONSTRAINT `fk_PayerUserId` FOREIGN KEY (`PayerUserID`) REFERENCES `Usr` (`UserID`) ON DELETE CASCADE ON UPDATE CASCADE
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8;
        ");
    }

    public function down(Schema $schema): void
    {
        $this->addSql('DROP TABLE `BalanceWatch`');
    }
}
