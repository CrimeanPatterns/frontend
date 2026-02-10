<?php

namespace AwardWallet\MainBundle\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
class Version20140530103712 extends AbstractMigration
{
    public function up(Schema $schema): void
    {
        $this->addSql("DROP TABLE ianHotel");
    }

    public function down(Schema $schema): void
    {
        $this->addSql("CREATE TABLE `ianHotel`
										(
											  `HotelID` int(11) NOT NULL,
											  `Name` varchar(250) NOT NULL,
											  `AirportCode` varchar(4) DEFAULT NULL,
											  `Address1` varchar(250) DEFAULT NULL,
											  `Address2` varchar(250) DEFAULT NULL,
											  `Address3` varchar(250) DEFAULT NULL,
											  `City` varchar(250) DEFAULT NULL,
											  `StateProvince` varchar(50) DEFAULT NULL,
											  `Country` varchar(4) DEFAULT NULL,
											  `PostalCode` varchar(20) DEFAULT NULL,
											  `Longitude` double DEFAULT NULL,
											  `Latitude` double DEFAULT NULL,
											  `LowRate` double DEFAULT NULL,
											  `HighRate` double DEFAULT NULL,
											  `MarketingLevel` int(11) DEFAULT NULL,
											  `Confidence` int(11) DEFAULT NULL,
											  `HotelModified` datetime DEFAULT NULL,
											  `PropertyType` varchar(20) DEFAULT NULL,
											  `TimeZone` varchar(40) DEFAULT NULL,
											  `GMTOffset` double DEFAULT NULL,
											  `YearPropertyOpened` int(11) DEFAULT NULL,
											  `YearPropertyRenovated` int(11) DEFAULT NULL,
											  `NativeCurrency` varchar(20) DEFAULT NULL,
											  `NumberOfRooms` int(11) DEFAULT NULL,
											  `NumberOfSuites` int(11) DEFAULT NULL,
											  `NumberOfFloors` int(11) DEFAULT NULL,
											  `CheckInTime` varchar(40) DEFAULT NULL,
											  `CheckOutTime` varchar(40) DEFAULT NULL,
											  `HasValetParking` varchar(1) DEFAULT NULL,
											  `HasContinentalBreakfast` varchar(1) DEFAULT NULL,
											  `HasInRoomMovies` varchar(1) DEFAULT NULL,
											  `HasSauna` varchar(1) DEFAULT NULL,
											  `HasWhirlpool` varchar(1) DEFAULT NULL,
											  `HasVoiceMail` varchar(1) DEFAULT NULL,
											  `Has24HourSecurity` varchar(1) DEFAULT NULL,
											  `HasParkingGarage` varchar(1) DEFAULT NULL,
											  `HasElectronicRoomKeys` varchar(1) DEFAULT NULL,
											  `HasCoffeeTeaMaker` varchar(1) DEFAULT NULL,
											  `HasSafe` varchar(1) DEFAULT NULL,
											  `HasVideoCheckOut` varchar(1) DEFAULT NULL,
											  `HasRestrictedAccess` varchar(1) DEFAULT NULL,
											  `HasInteriorRoomEntrance` varchar(1) DEFAULT NULL,
											  `HasExteriorRoomEntrance` varchar(1) DEFAULT NULL,
											  `HasCombination` varchar(1) DEFAULT NULL,
											  `HasFitnessFacility` varchar(1) DEFAULT NULL,
											  `HasGameRoom` varchar(1) DEFAULT NULL,
											  `HasTennisCourt` varchar(1) DEFAULT NULL,
											  `HasGolfCourse` varchar(1) DEFAULT NULL,
											  `HasInHouseDining` varchar(1) DEFAULT NULL,
											  `HasInHouseBar` varchar(1) DEFAULT NULL,
											  `HasHandicapAccessible` varchar(1) DEFAULT NULL,
											  `HasChildrenAllowed` varchar(1) DEFAULT NULL,
											  `HasPetsAllowed` varchar(1) DEFAULT NULL,
											  `HasTVInRoom` varchar(1) DEFAULT NULL,
											  `HasDataPorts` varchar(1) DEFAULT NULL,
											  `HasMeetingRooms` varchar(1) DEFAULT NULL,
											  `HasBusinessCenter` varchar(1) DEFAULT NULL,
											  `HasDryCleaning` varchar(1) DEFAULT NULL,
											  `HasIndoorPool` varchar(1) DEFAULT NULL,
											  `HasOutdoorPool` varchar(1) DEFAULT NULL,
											  `HasNonSmokingRooms` varchar(1) DEFAULT NULL,
											  `HasAirportTransportation` varchar(1) DEFAULT NULL,
											  `HasAirConditioning` varchar(1) DEFAULT NULL,
											  `HasClothingIron` varchar(1) DEFAULT NULL,
											  `HasWakeUpService` varchar(1) DEFAULT NULL,
											  `HasMiniBarInRoom` varchar(1) DEFAULT NULL,
											  `HasRoomService` varchar(1) DEFAULT NULL,
											  `HasHairDryer` varchar(1) DEFAULT NULL,
											  `HasCarRentDesk` varchar(1) DEFAULT NULL,
											  `HasFamilyRooms` varchar(1) DEFAULT NULL,
											  `HasKitchen` varchar(1) DEFAULT NULL,
											  `HasMap` varchar(20) DEFAULT NULL,
											  `PropertyDescription` varchar(2000) DEFAULT NULL,
											  `GDSChainCode` varchar(10) DEFAULT NULL,
											  `GDSChaincodeName` varchar(250) DEFAULT NULL,
											  `DestinationID` varchar(80) DEFAULT NULL,
											  `DrivingDirections` varchar(2000) DEFAULT NULL,
											  `NearbyAttractions` varchar(2000) DEFAULT NULL,
											  PRIMARY KEY (`HotelID`),
											  KEY `idx_Hotel_Name` (`Name`),
											  KEY `idx_ianHotel_Address1` (`Address1`)
									    )
									    ENGINE=InnoDB DEFAULT CHARSET=utf8");
    }
}
