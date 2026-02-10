<?php

declare(strict_types=1);

namespace AwardWallet\MainBundle\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20191127180000 extends AbstractMigration
{
    public function up(Schema $schema): void
    {
        $this->addSql("
            CREATE TABLE `UserCreditCard` (
              `UserCreditCardID` int(12) UNSIGNED NOT NULL AUTO_INCREMENT,
              `UserID` int(11) NOT NULL,
              `CreditCardID` int(11) NOT NULL COMMENT 'CreditCard.CreditCardID',
              `IsClosed` tinyint(1) DEFAULT '0' COMMENT 'Карта перестала собираться = она закрыта',
              `EarliestSeenDate` datetime DEFAULT NULL COMMENT 'Дата самой старой транзакции',
              `LastSeenDate` datetime DEFAULT NULL COMMENT 'Дата последней актуализации',
              `DetectedViaBank` tinyint(1) DEFAULT '0' COMMENT 'Если источник clickhouse.DetectedCards.CreditCardID или clickhouse.SubAccount.CreditCardID',
              `DetectedViaCobrand` tinyint(1) DEFAULT '0' COMMENT 'Если источник clickhouse.AccountHistory.CreditCardID',
              `DetectedViaQS` tinyint(1) DEFAULT '0' COMMENT 'Источник QsTransaction при Approvals=1 и сопоставлении RefCode',
              PRIMARY KEY (`UserCreditCardID`),
              UNIQUE KEY `UserCard_uniq` (`UserID`,`CreditCardID`),
              KEY `EarliestSeenDate` (`EarliestSeenDate`),
              CONSTRAINT `UserID_fk` FOREIGN KEY (`UserID`) REFERENCES `Usr` (`UserID`) ON DELETE CASCADE ON UPDATE CASCADE,
              CONSTRAINT `CreditCardID_fk` FOREIGN KEY (`CreditCardID`) REFERENCES `CreditCard` (`CreditCardID`) ON DELETE CASCADE ON UPDATE CASCADE
            ) ENGINE=InnoDB COMMENT='Наличие кредитных карт у пользователей из данных clickHouse, синхронизируется командой aw:update-user-creditcard';
        ");

        $this->addSql("ALTER TABLE `CreditCard` ADD `QsCreditCardID` INT(11) NULL DEFAULT NULL COMMENT 'QsCreditCard.QsCreditCardID'");
        $this->addSql("ALTER TABLE `CreditCard` ADD CONSTRAINT `QsCreditCardID_fk` FOREIGN KEY (`QsCreditCardID`) REFERENCES `QsCreditCard`(`QsCreditCardID`) ON DELETE SET NULL ON UPDATE SET NULL");
    }

    public function down(Schema $schema): void
    {
        $this->addSql("DROP TABLE `UserCreditCard`");
    }
}
