<?php

declare(strict_types=1);

namespace AwardWallet\MainBundle\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20190919094623 extends AbstractMigration
{
    public function up(Schema $schema): void
    {
        $this->addSql("
            ALTER TABLE `CreditCard` 
                ADD `HistoryPatterns` MEDIUMTEXT NULL DEFAULT NULL AFTER `Patterns`,
                ADD `CobrandProviderID` INT(11) NULL DEFAULT NULL AFTER `ProviderID`,
                ADD CONSTRAINT `CreditCard_ibfk_2` FOREIGN KEY (`CobrandProviderID`) REFERENCES `Provider` (`ProviderID`) ON DELETE CASCADE;
        ");
    }

    public function down(Schema $schema): void
    {
        $this->addSql("
            ALTER TABLE `CreditCard` 
            DROP FOREIGN KEY CreditCard_ibfk_2,
            DROP `CobrandProviderID`,
            DROP `HistoryPatterns`;
        ");
    }
}
