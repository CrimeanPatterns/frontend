<?php

declare(strict_types=1);

namespace AwardWallet\MainBundle\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20210520131313 extends AbstractMigration
{
    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE `UserSubAccountStorage` ADD `ClickDate` DATE NULL DEFAULT NULL AFTER `SuccessCheckDate`');
        $this->addSql('ALTER TABLE `UserCreditCardStorage` ADD `ClickDate` DATE NULL DEFAULT NULL AFTER `LastSeenDate`');

        $this->addSql('ALTER TABLE `UserSubAccountStorage` DROP INDEX `uniqUserSubAccountDate`, ADD UNIQUE `uniqUserSubAccountDate` (`UserID`, `SubAccountID`, `ClickDate`) USING BTREE');
        $this->addSql('ALTER TABLE `UserCreditCardStorage` DROP INDEX `uniqUserCardDate`, ADD UNIQUE `uniqUserCardDate` (`UserID`, `CreditCardID`, `ClickDate`) USING BTREE');
        
        $this->addSql('ALTER TABLE `UserSubAccountStorage` ADD `AccountID` INT(11) NULL DEFAULT NULL AFTER `UserID`');
        $this->addSql('ALTER TABLE `UserSubAccountStorage` ADD CONSTRAINT `fkUserSubAccountStorage_accountId` FOREIGN KEY (`AccountID`) REFERENCES `Account`(`AccountID`) ON DELETE CASCADE ON UPDATE CASCADE');
    }

    public function down(Schema $schema): void
    {
    }
}
