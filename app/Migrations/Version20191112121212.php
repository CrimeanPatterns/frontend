<?php

declare(strict_types=1);

namespace AwardWallet\MainBundle\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20191112121212 extends AbstractMigration
{
    public function up(Schema $schema): void
    {
        $this->addSql('
            ALTER TABLE `PromotionCard`
                ADD `IssuerProviderID` INT(11) NULL DEFAULT NULL AFTER `DealID`,
                ADD `CobrandProviderID` INT(11) NULL DEFAULT NULL AFTER `IssuerProviderID`
        ');
        $this->addSql('ALTER TABLE `PromotionCard` ADD CONSTRAINT `IssuerProviderID` FOREIGN KEY (`IssuerProviderID`) REFERENCES `Provider`(`ProviderID`) ON DELETE SET NULL ON UPDATE CASCADE');
        $this->addSql('ALTER TABLE `PromotionCard` ADD CONSTRAINT `CobrandProviderID` FOREIGN KEY (`CobrandProviderID`) REFERENCES `Provider`(`ProviderID`) ON DELETE SET NULL ON UPDATE CASCADE');
    }

    public function down(Schema $schema): void
    {
    }
}
