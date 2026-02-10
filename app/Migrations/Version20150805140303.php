<?php

namespace AwardWallet\MainBundle\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
class Version20150805140303 extends AbstractMigration
{
    public function up(Schema $schema): void
    {
        $this->addSql("
            CREATE TABLE `MilePrice` (
              `MilePriceID` int(11) unsigned NOT NULL AUTO_INCREMENT,
              `ProviderID` int(11) NOT NULL,
              `CurrencyID` int(11) unsigned NOT NULL,
              `NumberOfMiles` int(11) NOT NULL,
              `Price` decimal(11,2) NOT NULL,
              PRIMARY KEY (`MilePriceID`),
              UNIQUE KEY `ProviderID` (`ProviderID`,`CurrencyID`,`NumberOfMiles`),
              KEY `MilePrice_fk2` (`CurrencyID`),
              CONSTRAINT `MilePrice_fk1` FOREIGN KEY (`ProviderID`) REFERENCES `Provider` (`ProviderID`),
              CONSTRAINT `MilePrice_fk2` FOREIGN KEY (`CurrencyID`) REFERENCES `Currency` (`CurrencyID`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8;
        ");
    }

    public function down(Schema $schema): void
    {
        $this->addSql("DROP TABLE MilePrice");
    }
}
