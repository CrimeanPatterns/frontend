<?php

declare(strict_types=1);

namespace AwardWallet\MainBundle\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20210119063841 extends AbstractMigration
{
    public function up(Schema $schema): void
    {
        $this->addSql(
            "ALTER TABLE `Merchant` 
            ADD `ForcedShoppingCategoryID` int(4) DEFAULT NULL AFTER `ShoppingCategoryID`,
            ADD CONSTRAINT `FK_Merchant_ForcedShoppingCategory` FOREIGN KEY (`ForcedShoppingCategoryID`) REFERENCES `ShoppingCategory` (`ShoppingCategoryID`) ON DELETE SET NULL;"
        );
    }

    public function down(Schema $schema): void
    {
        $this->addSql(
            "ALTER TABLE `Merchant` 
            DROP FOREIGN KEY `FK_Merchant_ForcedShoppingCategory`,
            DROP `ForcedShoppingCategoryID`;"
        );
    }
}
