<?php

declare(strict_types=1);

namespace AwardWallet\MainBundle\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20190227073000 extends AbstractMigration
{
    public function up(Schema $schema): void
    {
        $this->addSql("ALTER TABLE `BalanceWatch` DROP FOREIGN KEY `fk_PayerUserId`");
        $this->addSql("ALTER TABLE `BalanceWatch` ADD CONSTRAINT `fk_PayerUserID` FOREIGN KEY (`PayerUserID`) REFERENCES `Usr` (`UserID`) ON DELETE SET NULL ON UPDATE CASCADE");
        $this->addSql("
            ALTER TABLE `BalanceWatch` ADD `ProviderID` INT(11) DEFAULT NULL  AFTER `AccountID`,
                ADD KEY `fk_ProviderId` (`ProviderID`),
                ADD CONSTRAINT `fk_ProviderID` FOREIGN KEY (`ProviderID`) REFERENCES `Provider`(`ProviderID`) ON DELETE CASCADE ON UPDATE CASCADE
        ");
    }

    public function down(Schema $schema): void
    {
    }
}
