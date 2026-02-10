<?php

declare(strict_types=1);

namespace AwardWallet\MainBundle\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20210125065558 extends AbstractMigration
{
    public function up(Schema $schema): void
    {
        $this->addSql("CREATE TABLE `AirlineGroup` (
          `AirlineGroupID` int NOT NULL AUTO_INCREMENT,
          `Name` varchar(250) NOT NULL,
          `ProviderID` int NOT NULL COMMENT 'провайдер чьи мили будут тратиться (TODO: сделать not null после заполнения)',
          PRIMARY KEY (`AirlineGroupID`),
          UNIQUE KEY (`Name`),
          FOREIGN KEY `fkProvider` (`ProviderID`) REFERENCES `Provider` (`ProviderID`) ON DELETE CASCADE
        ) ENGINE=InnoDB COMMENT='Часть схемы RewardsPrice'");

        $this->addSql("CREATE TABLE `AirlineGroupAirline` (
          `AirlineGroupAirlineID` int(11) NOT NULL AUTO_INCREMENT,
          `AirlineGroupID` int(11) NOT NULL,
          `AirlineID` int(11) NOT NULL,
          PRIMARY KEY (`AirlineGroupAirlineID`),
          UNIQUE KEY `Airline` (`AirlineGroupID`,`AirlineID`),
          CONSTRAINT `fkAirlineGroup` FOREIGN KEY (`AirlineGroupID`) REFERENCES `AirlineGroup` (`AirlineGroupID`) ON DELETE CASCADE,
          CONSTRAINT `fkAirline` FOREIGN KEY (`AirlineID`) REFERENCES `Airline` (`AirlineID`) ON DELETE CASCADE
        ) ENGINE=InnoDB COMMENT='Часть схемы RewardsPrice'");

        $this->addSql("drop table AwardChartAirline");
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
    }
}
