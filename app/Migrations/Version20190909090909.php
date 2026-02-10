<?php

declare(strict_types=1);

namespace AwardWallet\MainBundle\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20190909090909 extends AbstractMigration
{
    public function up(Schema $schema): void
    {
        $this->addSql("
            CREATE TABLE `PurchaseStat` (
              `PurchaseStatID` int(11) NOT NULL AUTO_INCREMENT,
              `ProviderID` int(11) NOT NULL,
              `MinDuration` decimal(10,2) DEFAULT NULL COMMENT 'Минимальное время выполнения в часах',
              `MaxDuration` decimal(10,2) DEFAULT NULL COMMENT 'Максимальное время выполнения в часах',
              `CalcDuration` int(11) DEFAULT NULL COMMENT 'Высчитанное время в секундах',
              `TransactionCount` int(11) DEFAULT NULL COMMENT 'Количество транзакций, по которым вычислялось среднее время передачи',
              `TimeDeviation` int(11) DEFAULT NULL COMMENT 'Среднее отклонение времени в секундах',
              PRIMARY KEY (`PurchaseStatID`),
              UNIQUE KEY `ProviderID` (`ProviderID`),
              CONSTRAINT `ProviderID_fk` FOREIGN KEY (`ProviderID`) REFERENCES `Provider` (`ProviderID`) ON DELETE CASCADE ON UPDATE CASCADE
            ) ENGINE=InnoDB;
        ");

        $this->addSql("
            CREATE TABLE `TransferStat` (
              `TransferStatID` int(11) NOT NULL AUTO_INCREMENT,
              `SourceProviderID` int(11) NOT NULL,
              `TargetProviderID` int(11) NOT NULL,
              `SourceRate` int(11) DEFAULT NULL,
              `TargetRate` int(11) DEFAULT NULL,
              `MinDuration` decimal(10,2) DEFAULT NULL COMMENT 'Минимальное время выполнения в часах',
              `MaxDuration` decimal(10,2) DEFAULT NULL COMMENT 'Максимальное время выполнения в часах',
              `CalcDuration` int(11) DEFAULT NULL COMMENT 'Высчитанное время в секундах в секундах',
              `TransactionCount` int(11) DEFAULT NULL COMMENT 'Количество транзакций, по которым вычислялось среднее время передачи',
              `TimeDeviation` int(11) DEFAULT NULL COMMENT 'Среднее отклонение времени в секундах',
              PRIMARY KEY (`TransferStatID`),
              UNIQUE KEY `SourceTargetTS_unique` (`SourceProviderID`,`TargetProviderID`),
              CONSTRAINT `SourceProviderTS_fk` FOREIGN KEY (`SourceProviderID`) REFERENCES `Provider` (`ProviderID`) ON DELETE CASCADE ON UPDATE CASCADE,
              CONSTRAINT `TargetProviderTS_fk` FOREIGN KEY (`TargetProviderID`) REFERENCES `Provider` (`ProviderID`) ON DELETE CASCADE ON UPDATE CASCADE
            ) ENGINE=InnoDB;
        ");
    }

    public function down(Schema $schema): void
    {
        $this->addSql('DROP TABLE `PurchaseStat`, `TransferStat`');
    }
}
