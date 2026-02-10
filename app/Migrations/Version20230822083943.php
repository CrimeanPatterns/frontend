<?php

declare(strict_types=1);

namespace AwardWallet\MainBundle\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20230822083943 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql(/** @lang MySQL */" 
            CREATE TABLE `RAFlightRouteSearchVolume` (
			  `RAFlightRouteSearchVolumeID` int(11) NOT NULL AUTO_INCREMENT,
			  `ProviderID` int(11) NOT NULL,
			  `DepartureAirport` varchar(3) NOT NULL,
			  `ArrivalAirport` varchar(3) NOT NULL,
			  `ClassOfService` varchar(40) NOT NULL,
			  `TimesSearched` int(11) NOT NULL,
              `LastSearch` datetime NOT NULL,
			  PRIMARY KEY (`RAFlightRouteSearchVolumeID`),
              CONSTRAINT `RAFlightRouteSearchVolume_ProviderID_fk` FOREIGN KEY (`ProviderID`) REFERENCES `Provider` (`ProviderID`) ON DELETE CASCADE,
              UNIQUE KEY `idxRAFlightRouteSearchVolumeFilterKey` (`ProviderID`,`DepartureAirport`,`ArrivalAirport`,`ClassOfService`));

            CREATE TABLE `RAFlightRoute` (
			  `RAFlightRouteID` int(11) NOT NULL AUTO_INCREMENT,
			  `ProviderID` int(11) NOT NULL,
			  `DepartureAirport` varchar(3) NOT NULL,
			  `ArrivalAirport` varchar(3) NOT NULL,
			  `ClassOfService` varchar(40) NOT NULL,
			  `Airline` varchar(2) NOT NULL,
			  `TimesSeen` int(11) NOT NULL,
              `LastParsedDate` datetime NOT NULL,
			  PRIMARY KEY (`RAFlightRouteID`),
              CONSTRAINT `RAFlightRoute_ProviderID_fk` FOREIGN KEY (`ProviderID`) REFERENCES `Provider` (`ProviderID`) ON DELETE CASCADE ON UPDATE CASCADE,
              UNIQUE KEY `idxRAFlightRouteFilterKey` (`ProviderID`,`DepartureAirport`,`ArrivalAirport`,`ClassOfService`, `Airline`));

");
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql(/** @lang MySQL */"
            DROP TABLE `RAFlightRouteSearchVolume`;
            DROP TABLE `RAFlightRoute`;
            ");
    }
}
