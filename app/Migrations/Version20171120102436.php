<?php

namespace AwardWallet\MainBundle\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
class Version20171120102436 extends AbstractMigration
{
    public function up(Schema $schema): void
    {
        $this->addSql("
            ALTER TABLE `Merchant` 
                ADD `ShoppingCategoryID` INT  NULL  DEFAULT NULL AFTER `Name`,
                ADD CONSTRAINT `FK_Merchant_ShoppingCategory` FOREIGN KEY (`ShoppingCategoryID`) REFERENCES `ShoppingCategory` (`ShoppingCategoryID`) ON DELETE SET NULL;
        ");
    }

    public function down(Schema $schema): void
    {
        $this->addSql("
            ALTER TABLE `Merchant` 
            DROP FOREIGN KEY `FK_Merchant_ShoppingCategory`,
            DROP `ShoppingCategoryID`;
        ");
    }
}
