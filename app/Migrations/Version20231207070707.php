<?php

declare(strict_types=1);

namespace AwardWallet\MainBundle\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20231207070707 extends AbstractMigration
{
    public function up(Schema $schema): void
    {
        $this->addSql('
            CREATE TABLE `HotelApiResult` (
              `HotelApiDataID` int NOT NULL AUTO_INCREMENT,
              `ProviderID` int NOT NULL,
              `HotelName` varchar(255) NOT NULL,
              `CheckInDate` datetime NOT NULL,
              `CheckOutDate` datetime DEFAULT NULL,
              `HotelDescription` varchar(255) DEFAULT NULL,
              `NumberOfNights` tinyint unsigned NOT NULL,
              `PointsPerNight` mediumint unsigned DEFAULT NULL,
              `CashPerNight` decimal(10,2) DEFAULT NULL,
              `OriginalCurrency` char(16) DEFAULT NULL,
              `Distance` decimal(10,2) DEFAULT NULL,
              `Rating` decimal(5,3) DEFAULT NULL,
              `NumberOfReviews` mediumint unsigned DEFAULT NULL,
              `Phone` varchar(64) DEFAULT NULL,
              `Address` varchar(255) NOT NULL,
              `AddressLine` varchar(255) DEFAULT NULL,
              `City` varchar(64) DEFAULT NULL,
              `State` varchar(64) DEFAULT NULL,
              `Country` varchar(64) DEFAULT NULL,
              `PostalCode` varchar(32) DEFAULT NULL,
              `Lat` decimal(8,6) DEFAULT NULL,
              `Lng` decimal(8,6) DEFAULT NULL,
              PRIMARY KEY (`HotelApiDataID`),
              UNIQUE KEY `ProviderID` (`ProviderID`,`HotelName`,`CheckInDate`,`CheckOutDate`,`NumberOfNights`,`Address`),
              CONSTRAINT `hotelApiResult_providerId` FOREIGN KEY (`ProviderID`) REFERENCES `Provider` (`ProviderID`) ON DELETE CASCADE ON UPDATE CASCADE
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3
        ');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('DROP TABLE `HotelApiResult`');
    }
}
