<?php

namespace AwardWallet\MainBundle\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
class Version20130823151024 extends AbstractMigration
{
    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql("CREATE TABLE `Flights` (
  `FlightID` int(11) NOT NULL AUTO_INCREMENT,
  `TripID` int(11) NOT NULL,
  `TripSegmentID` int(11) NOT NULL,
  `AccountID` int(11) DEFAULT NULL,
  `RecordLocator` varchar(20) DEFAULT NULL,
  `TravelPlanID` int(11) DEFAULT NULL,
  `Hidden` smallint(6) NOT NULL DEFAULT '0',
  `Parsed` int(11) NOT NULL DEFAULT '0',
  `AirlineName` varchar(250) DEFAULT NULL,
  `UserID` int(11) DEFAULT NULL,
  `Notes` varchar(4000) DEFAULT NULL,
  `ProviderID` int(11) DEFAULT NULL,
  `Moved` int(11) NOT NULL DEFAULT '0',
  `UpdateDate` datetime DEFAULT NULL,
  `ConfFields` varchar(250) DEFAULT NULL,
  `Category` int(11) NOT NULL DEFAULT '1',
  `Direction` tinyint(4) NOT NULL DEFAULT '0',
  `CreateDate` datetime NOT NULL,
  `UserPermission` tinyint(4) NOT NULL DEFAULT '0',
  `LastOfferDate` datetime DEFAULT NULL,
  `CouponCode1` varchar(250) DEFAULT NULL,
  `CouponCode2` varchar(250) DEFAULT NULL,
  `SavingsAmount` float DEFAULT NULL,
  `ProcessedByTC` tinyint(4) NOT NULL DEFAULT '0',
  `SavingsConfirmed` float DEFAULT NULL,
  `AllowChangeLessSavings` tinyint(4) NOT NULL DEFAULT '0',
  `Cancelled` tinyint(2) NOT NULL DEFAULT '0',
  `Hash` varchar(64) DEFAULT NULL,
  `PlanIndex` int(11) NOT NULL DEFAULT '0',
  `UserAgentID` int(11) DEFAULT NULL,
  `Copied` tinyint(4) NOT NULL DEFAULT '0',
  `Modified` tinyint(4) NOT NULL DEFAULT '0',
  `MailDate` datetime DEFAULT NULL,
  `DepCode` varchar(10) DEFAULT NULL,
  `DepName` varchar(250) NOT NULL,
  `DepDate` datetime NOT NULL,
  `ArrCode` varchar(10) DEFAULT NULL,
  `ArrName` varchar(250) NOT NULL,
  `ArrDate` datetime NOT NULL,
  `FlightNumber` varchar(20) DEFAULT NULL,
  `DepGeoTagID` int(11) DEFAULT NULL,
  `ArrGeoTagID` int(11) DEFAULT NULL,
  `CheckinNotified` int(11) NOT NULL DEFAULT '0',
  `ShareCode` varchar(32) DEFAULT NULL,
  PRIMARY KEY (`FlightID`),
  UNIQUE KEY `AccountID` (`AccountID`,`UserID`,`UserAgentID`,`RecordLocator`,`Direction`),
  KEY `TravelPlanID` (`TravelPlanID`),
  KEY `UserID` (`UserID`),
  KEY `UserAgentID` (`UserAgentID`),
  KEY `ArrGeoTagID` (`ArrGeoTagID`),
  KEY `DepGeoTagID` (`DepGeoTagID`),
  KEY `AccountID_2` (`AccountID`,`Hash`),
  KEY `UserID_2` (`UserID`,`Hash`),
  KEY `ArrCode` (`ArrCode`),
  KEY `DepDate` (`DepDate`,`CheckinNotified`),
  CONSTRAINT `Flights_ibfk_6` FOREIGN KEY (`DepGeoTagID`) REFERENCES `GeoTag` (`GeoTagID`) ON DELETE SET NULL,
  CONSTRAINT `Flights_ibfk_1` FOREIGN KEY (`TravelPlanID`) REFERENCES `TravelPlan` (`TravelPlanID`) ON DELETE SET NULL,
  CONSTRAINT `Flights_ibfk_2` FOREIGN KEY (`AccountID`) REFERENCES `Account` (`AccountID`) ON DELETE CASCADE,
  CONSTRAINT `Flights_ibfk_3` FOREIGN KEY (`UserAgentID`) REFERENCES `UserAgent` (`UserAgentID`) ON DELETE CASCADE,
  CONSTRAINT `Flights_ibfk_4` FOREIGN KEY (`UserID`) REFERENCES `Usr` (`UserID`) ON DELETE CASCADE,
  CONSTRAINT `Flights_ibfk_5` FOREIGN KEY (`ArrGeoTagID`) REFERENCES `GeoTag` (`GeoTagID`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8;");
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql("DROP TABLE `Flights`");
    }
}
