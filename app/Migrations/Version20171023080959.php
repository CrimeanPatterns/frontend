<?php

namespace AwardWallet\MainBundle\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
class Version20171023080959 extends AbstractMigration
{
    public function up(Schema $schema): void
    {
        $this->addSql("
            ALTER TABLE `AccountHistory` 
                ADD `ShoppingCategoryID` INT  NULL  DEFAULT NULL  AFTER `Multiplier`,
                ADD CONSTRAINT `FK_AccountHistory_Category` FOREIGN KEY (`ShoppingCategoryID`) REFERENCES `ShoppingCategory` (`ShoppingCategoryID`) ON DELETE SET NULL;
        ");
    }

    public function down(Schema $schema): void
    {
        $this->addSql("
            ALTER TABLE `AccountHistory` 
            DROP FOREIGN KEY `FK_AccountHistory_Category`,
            DROP `ShoppingCategoryID`;
        ");
    }
}
