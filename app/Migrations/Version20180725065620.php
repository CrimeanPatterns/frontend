<?php

declare(strict_types=1);

namespace AwardWallet\MainBundle\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20180725065620 extends AbstractMigration
{
    public function up(Schema $schema): void
    {
        $this->addSql("ALTER TABLE `CreditCardShoppingCategory` DROP `StartLimit`, DROP `StopLimit`;");
        $this->addSql("
            ALTER TABLE `ShoppingCategory`
                ADD `MatchingPriority` int(11) NOT NULL DEFAULT 0 COMMENT 'Приоритет при выборе категории для конкретного мерчанта', 
                ADD `ProviderID` int(11) NULL DEFAULT NULL,
                ADD CONSTRAINT `ShoppingCategory_ibfk_1` FOREIGN KEY (`ProviderID`) REFERENCES `Provider` (`ProviderID`) ON DELETE SET NULL;
        ");
        $this->addSql("
            CREATE TABLE `MerchantReport` (
              `MerchantID` int(11) NOT NULL,
              `CreditCardID` int(11) NOT NULL,
              `ShoppingCategoryID` int(11) NOT NULL,
              PRIMARY KEY (`MerchantID`,`CreditCardID`,`ShoppingCategoryID`),
              CONSTRAINT `MerchantReport_ibfk_1` FOREIGN KEY (`MerchantID`) REFERENCES `Merchant` (`MerchantID`) ON DELETE CASCADE,
              CONSTRAINT `MerchantReport_ibfk_2` FOREIGN KEY (`CreditCardID`) REFERENCES `CreditCard` (`CreditCardID`) ON DELETE CASCADE,
              CONSTRAINT `MerchantReport_ibfk_3` FOREIGN KEY (`ShoppingCategoryID`) REFERENCES `ShoppingCategory` (`ShoppingCategoryID`) ON DELETE CASCADE
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8;
        ");
    }

    public function down(Schema $schema): void
    {
        $this->addSql("
            ALTER TABLE `CreditCardShoppingCategory`
                ADD `StartLimit` FLOAT  NULL  DEFAULT NULL COMMENT 'set a limit in USD after which earning additional bonnus points will begin',
                ADD `StopLimit` FLOAT  NULL  DEFAULT NULL COMMENT 'set a limit in USD after which earning bonnus points will stop';
        ");
        $this->addSql("
            ALTER TABLE `ShoppingCategory` 
                DROP FOREIGN KEY `ShoppingCategory_ibfk_1`,
                DROP INDEX `ShoppingCategory_ibfk_1`,
                DROP `MatchingPriority`,
                DROP `ProviderID`;
        ");
        $this->addSql("DROP TABLE `MerchantReport`");
    }
}
