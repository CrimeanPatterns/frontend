<?php

declare(strict_types=1);

namespace AwardWallet\MainBundle\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20210407120000 extends AbstractMigration
{
    public function up(Schema $schema): void
    {
        $this->addSql(
            "
            ALTER TABLE `UserCreditCard`
                ADD `AccountID` INT NULL DEFAULT NULL,
                ADD `SubAccountID` INT NULL DEFAULT NULL,
                ADD `SourcePlace` TINYINT(2) NULL DEFAULT NULL
        ");
        $this->addSql("ALTER TABLE `UserCreditCard` ADD CONSTRAINT `UserCreditCard_fk_AccountID` FOREIGN KEY (`AccountID`) REFERENCES `Account`(`AccountID`) ON DELETE SET NULL ON UPDATE CASCADE");
        $this->addSql("ALTER TABLE `UserCreditCard` ADD CONSTRAINT `UserCreditCard_fk_SubAccountID` FOREIGN KEY (`SubAccountID`) REFERENCES `SubAccount`(`SubAccountID`) ON DELETE SET NULL ON UPDATE CASCADE");
    }

    public function down(Schema $schema): void
    {
    }
}
