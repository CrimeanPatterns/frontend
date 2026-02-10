<?php

declare(strict_types=1);

namespace AwardWallet\MainBundle\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20180813080532 extends AbstractMigration
{
    public function up(Schema $schema): void
    {
        $this->addSql("ALTER TABLE `AccountHistory` ADD INDEX `HistoryDataIndex2` (`MerchantID`, `ShoppingCategoryID`);");
        $this->addSql("
            CREATE TABLE `MasterSlaveCategoryReport` (
              `MasterCategoryID` int(11) NOT NULL,
              `SlaveCategoryID` int(11) NOT NULL,
              PRIMARY KEY (`MasterCategoryID`,`SlaveCategoryID`),
              CONSTRAINT `MasterSlaveCategoryReport_ibfk_1` FOREIGN KEY (`MasterCategoryID`) REFERENCES `ShoppingCategory` (`ShoppingCategoryID`) ON DELETE CASCADE,
              CONSTRAINT `MasterSlaveCategoryReport_ibfk_2` FOREIGN KEY (`SlaveCategoryID`) REFERENCES `ShoppingCategory` (`ShoppingCategoryID`) ON DELETE CASCADE
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8;
        ");
    }

    public function down(Schema $schema): void
    {
        $this->addSql("ALTER TABLE `AccountHistory` DROP INDEX `HistoryDataIndex2`;");
        $this->addSql("DROP TABLE `MasterSlaveCategoryReport`");
    }
}
