<?php

declare(strict_types=1);

namespace AwardWallet\MainBundle\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20181001084728 extends AbstractMigration
{
    public function up(Schema $schema): void
    {
        $this->addSql("
            ALTER TABLE `CreditCardShoppingCategoryGroup` 
                CHANGE `ShoppingCategoryGroupID` `ShoppingCategoryGroupID` INT(11)  NULL  DEFAULT NULL,
                ADD `SortIndex` INT(4) NOT NULL DEFAULT 0;
        ");
        $this->addSql("INSERT INTO `CreditCardShoppingCategoryGroup` (`CreditCardShoppingCategoryGroupID`, `CreditCardID`, `ShoppingCategoryGroupID`, `Multiplier`, `StartDate`, `Description`) VALUES (NULL, 1, NULL, 1.5, NULL, '1.5x UR points'), (NULL, 26, NULL, 2, NULL, '2x MR points');");
    }

    public function down(Schema $schema): void
    {
    }
}
