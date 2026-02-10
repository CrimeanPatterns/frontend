<?php

namespace AwardWallet\MainBundle\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
class Version20171030115113 extends AbstractMigration
{
    public function up(Schema $schema): void
    {
        $this->addSql("
            ALTER TABLE `CreditCardShoppingCategory` 
                CHANGE `ShoppingCategoryID` `ShoppingCategoryID` INT(11)  NULL,
                DROP FOREIGN KEY `CreditCardShoppingCategory_ibfk_2`,
                DROP INDEX `ShoppingCategoryID`;
        ");
        $this->addSql("
            ALTER TABLE `CreditCardShoppingCategory` 
                ADD FOREIGN KEY (`ShoppingCategoryID`) REFERENCES `ShoppingCategory` (`ShoppingCategoryID`) ON DELETE CASCADE,
                ADD UNIQUE INDEX `unique_key` (`CreditCardID`, `ShoppingCategoryID`, `StartDate`);
        ");
    }

    public function down(Schema $schema): void
    {
    }
}
