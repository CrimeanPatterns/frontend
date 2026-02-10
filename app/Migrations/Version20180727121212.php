<?php

declare(strict_types=1);

namespace AwardWallet\MainBundle\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20180727121212 extends AbstractMigration
{
    public function up(Schema $schema): void
    {
        $this->addSql("
            CREATE TABLE `AAUCreditsTransaction` (
              `AAUCreditsTransactionID` int(11) unsigned NOT NULL AUTO_INCREMENT,
              `UserID` int(11) NOT NULL,
              `AccountID` int(11) DEFAULT NULL,
              `TransactionType` smallint(5) NOT NULL COMMENT 'AAUCreditsTransaction::TRANSACTION_TYPE',
              `Amount` int(11) NOT NULL COMMENT 'Сумма транзакции',
              `Balance` int(11) NOT NULL COMMENT 'Баланс после совершения транзакции',
              `CreationDate` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
              PRIMARY KEY (`AAUCreditsTransactionID`),
              CONSTRAINT `fk_Account_Id` FOREIGN KEY (`AccountID`) REFERENCES `Account` (`AccountID`) ON DELETE SET NULL ON UPDATE CASCADE,
              CONSTRAINT `fk_User_Id` FOREIGN KEY (`UserID`) REFERENCES `Usr` (`UserID`) ON DELETE CASCADE ON UPDATE CASCADE
            ) ENGINE=InnoDB CHARSET=utf8;
        ");
    }

    public function down(Schema $schema): void
    {
        $this->addSql("DROP TABLE `AAUCreditsTransaction`");
    }
}
