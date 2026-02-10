<?php

namespace AwardWallet\MainBundle\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
class Version20151212092109 extends AbstractMigration
{
    public function up(Schema $schema): void
    {
        $this->addSql("
            CREATE TABLE `BusinessInfo` (
              `BusinessInfoID` int(11) unsigned NOT NULL AUTO_INCREMENT,
              `UserID` int(11) NOT NULL COMMENT 'Ссылка на бизнес-юзера',
              `Balance` decimal(12,2) NOT NULL COMMENT 'Баланс',
              `Discount` int(11) NOT NULL DEFAULT '0' COMMENT 'Скидка на добавление юзера (приконект или family member). На апгрейд до Aw Plus приконекченных не влияет',
              `TrialEndDate` datetime DEFAULT NULL COMMENT 'Дата окончания триала',
              PRIMARY KEY (`BusinessInfoID`),
              UNIQUE KEY `BusInfo_UserID_FK` (`UserID`),
              CONSTRAINT `BusInfo_UserID_FK` FOREIGN KEY (`UserID`) REFERENCES `Usr` (`UserID`) ON DELETE CASCADE
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8;
        ");

        $this->addSql("ALTER TABLE `AbBookerInfo` ADD `Discount` INT  NOT NULL  DEFAULT '0'  COMMENT 'Скидка на закрытие букзапросов'  AFTER `InboundPercent`");
        $this->addSql("
            CREATE TABLE `BusinessTransaction` (
              `BusinessTransactionID` int(11) unsigned NOT NULL AUTO_INCREMENT,
              `UserID` int(11) NOT NULL COMMENT 'ID бизнеса',
              `CreateDate` datetime NOT NULL COMMENT 'Дата создания',
              `Type` tinyint(4) NOT NULL COMMENT 'Тип транзакции (добавление юзера, удаление, закрытие букзапроса и т.д.)',
              `Amount` decimal(12,2) NOT NULL DEFAULT '0.00' COMMENT 'Сумма транзакции',
              `Balance` decimal(12,2) NOT NULL DEFAULT '0.00' COMMENT 'Остаток на балансе после вычета',
              `SourceID` int(11) DEFAULT NULL COMMENT 'UserAgentID, AbRequestID',
              `SourceDesc` varchar(250) DEFAULT NULL COMMENT 'Тест, который показывается при формировании description, даже когда SourceID не существует',
              PRIMARY KEY (`BusinessTransactionID`),
              KEY `BusTrans_CreateDate` (`CreateDate`),
              KEY `BusTrans_SourceID` (`SourceID`),
              KEY `BusTrans_UserID_FK` (`UserID`),
              CONSTRAINT `BusTrans_UserID_FK` FOREIGN KEY (`UserID`) REFERENCES `Usr` (`UserID`) ON DELETE CASCADE
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8;
        ");

        $sql = "
          SELECT
              UserID
          FROM Usr
          WHERE
              AccountLevel = " . ACCOUNT_LEVEL_BUSINESS . "
        ";

        foreach ($this->connection->query($sql) as $row) {
            $this->addSql("
              INSERT INTO BusinessInfo (UserID, Balance, TrialEndDate)
              VALUES ('{$row["UserID"]}', 0, null)
            ");
        }
        $this->addSql("
          UPDATE BusinessInfo SET Discount = 100 WHERE UserID = 116000
        ");
        $this->addSql("
          UPDATE AbBookerInfo SET Discount = 100 WHERE UserID = 116000
        ");
    }

    public function down(Schema $schema): void
    {
        $this->addSql("DROP TABLE `BusinessTransaction`");
        $this->addSql("DROP TABLE `BusinessInfo`");
        $this->addSql("ALTER TABLE `AbBookerInfo` DROP `Discount`");
    }
}
