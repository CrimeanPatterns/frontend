<?php

declare(strict_types=1);

namespace AwardWallet\MainBundle\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20210121212121 extends AbstractMigration
{
    public function up(Schema $schema): void
    {
        $this->addSql("
CREATE TABLE `UserSubAccountStorage` (
  `UserSubAccountStorageID` int(11) NOT NULL AUTO_INCREMENT,
  `UserID` int(11) NOT NULL,
  `SubAccountID` int(11) NOT NULL,
  `Balance` decimal(18,2) DEFAULT NULL,
  `SuccessCheckDate` datetime DEFAULT NULL,
  `CreationDate` date NOT NULL,
  PRIMARY KEY (`UserSubAccountStorageID`),
  CONSTRAINT `fkUserSubAccountStorage_userId` FOREIGN KEY (`UserID`) REFERENCES `Usr` (`UserID`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `fkUserSubAccountStorage_subAccountId` FOREIGN KEY (`SubAccountID`) REFERENCES `SubAccount` (`SubAccountID`) ON DELETE CASCADE ON UPDATE CASCADE,
  UNIQUE KEY `uniqUserSubAccountDate` (`UserID`,`SubAccountID`,`CreationDate`) USING BTREE
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COMMENT='refs #19836';
        ");

        $this->addSql("
CREATE TABLE `UserCreditCardStorage` (
  `UserCreditCardStorageID` int(11) NOT NULL AUTO_INCREMENT,
  `UserID` int(11) NOT NULL,
  `CreditCardID` int(11) NOT NULL,
  `EarliestSeenDate` datetime DEFAULT NULL,
  `LastSeenDate` datetime DEFAULT NULL,
  `CreationDate` date NOT NULL,
  PRIMARY KEY (`UserCreditCardStorageID`),
  CONSTRAINT `fkUserCreditCardStorage_userId` FOREIGN KEY (`UserID`) REFERENCES `Usr` (`UserID`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `fkUserCreditCardStorage_cardId` FOREIGN KEY (`CreditCardID`) REFERENCES `CreditCard` (`CreditCardID`) ON DELETE CASCADE ON UPDATE CASCADE,
  UNIQUE KEY `uniqUserCardDate` (`UserID`,`CreditCardID`,`CreationDate`) USING BTREE
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COMMENT='refs #19836';
        ");
    }

    public function down(Schema $schema): void
    {
        $this->addSql('DROP TABLE `UserSubAccountStorage`');
        $this->addSql('DROP TABLE `UserCreditCardStorage`');
    }
}
