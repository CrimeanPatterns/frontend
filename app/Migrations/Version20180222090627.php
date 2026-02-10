<?php

namespace AwardWallet\MainBundle\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
class Version20180222090627 extends AbstractMigration
{
    public function up(Schema $schema): void
    {
        $this->addSql("SET FOREIGN_KEY_CHECKS=0");
        $this->addSql("
          ALTER TABLE Trip ADD COLUMN TravelAgencyID INT(11) AFTER ProviderID, 
          ADD CONSTRAINT fk_trip_travel_agency FOREIGN KEY (TravelAgencyID) REFERENCES Provider (ProviderID),
          ADD COLUMN TravelAgencyParsedAccountNumbers TINYTEXT NULL COMMENT 'Parsed account numbers in Simple Array format (e.g. \"80050130,49304756\")',
          ADD COLUMN TicketNumbers TEXT,
          ADD COLUMN ParsedStatus VARCHAR(20) DEFAULT NULL,
          ADD COLUMN AirlineID INT(11) DEFAULT NULL AFTER AirlineName,
          ADD CONSTRAINT fk_trip_issuing_airline FOREIGN KEY (AirlineID) REFERENCES Airline (AirlineID) ON DELETE SET NULL ON UPDATE CASCADE
        ");
        $this->addSql("
          ALTER TABLE Reservation ADD COLUMN TravelAgencyID INT(11) AFTER ProviderID, 
          ADD CONSTRAINT fk_reservation_travel_agency FOREIGN KEY (TravelAgencyID) REFERENCES Provider (ProviderID),
          ADD COLUMN TravelAgencyParsedAccountNumbers TINYTEXT NULL COMMENT 'Parsed account numbers in Simple Array format (e.g. \"80050130,49304756\")',
          ADD COLUMN ParsedStatus VARCHAR(20) DEFAULT NULL
        ");
        $this->addSql("
          ALTER TABLE Rental ADD COLUMN TravelAgencyID INT(11) AFTER ProviderID, 
          ADD CONSTRAINT fk_rental_travel_agency FOREIGN KEY (TravelAgencyID) REFERENCES Provider (ProviderID),
          ADD COLUMN TravelAgencyParsedAccountNumbers TINYTEXT NULL COMMENT 'Parsed account numbers in Simple Array format (e.g. \"80050130,49304756\")',
          ADD COLUMN ParsedStatus VARCHAR(20) DEFAULT NULL,
          ADD COLUMN RentalCompanyName VARCHAR(80) DEFAULT NULL AFTER ProviderName
        ");
        $this->addSql("
          ALTER TABLE Restaurant ADD COLUMN TravelAgencyID INT(11) AFTER ProviderID, 
          ADD CONSTRAINT fk_event_travel_agency FOREIGN KEY (TravelAgencyID) REFERENCES Provider (ProviderID),
          ADD COLUMN GuestCount TINYINT NULL,
          ADD COLUMN TravelAgencyParsedAccountNumbers TINYTEXT NULL COMMENT 'Parsed account numbers in Simple Array format (e.g. \"80050130,49304756\")',
          ADD COLUMN Fax VARCHAR(80) DEFAULT NULL AFTER Phone,
          ADD COLUMN ParsedStatus VARCHAR(20) DEFAULT NULL
        ");
        $this->addSql("
          ALTER TABLE TripSegment 
            ADD COLUMN OperatingAirlineID INT(11) DEFAULT NULL COMMENT 'Airline that is actually operating the flight' AFTER AirlineName,
            ADD COLUMN OperatingAirlineName VARCHAR(250) DEFAULT NULL COMMENT 'Name of the airline that is actually operating the flight' AFTER AirlineName,
            ADD COLUMN OperatingAirlineFlightNumber VARCHAR(20) DEFAULT NULL COMMENT 'Flight number used by operating airline' AFTER FlightNumber,
            ADD COLUMN ServiceClasses varchar(10) DEFAULT NULL COMMENT 'https://en.wikipedia.org/wiki/Fare_basis_code placed together as a string',
            ADD CONSTRAINT fk_trip_segment_operating_airline FOREIGN KEY (OperatingAirlineID) REFERENCES Airline (AirlineID),
            CHANGE AirlineID AirlineID INT(11) DEFAULT NULL COMMENT 'Airline that is shown in the ticket',
            CHANGE AirlineName AirlineName VARCHAR(250) DEFAULT NULL COMMENT 'Name of the airline that is shown in the ticket',
            ADD COLUMN MarketingAirlineConfirmationNumber VARCHAR(50) DEFAULT NULL,
            ADD COLUMN OperatingAirlineConfirmationNumber VARCHAR(50) DEFAULT NULL,
            ADD COLUMN MarketingAirlinePhoneNumbers TINYTEXT DEFAULT '',
            ADD COLUMN OperatingAirlinePhoneNumbers TINYTEXT DEFAULT '',
            ADD COLUMN ServiceName VARCHAR(50) COMMENT 'Short name of the particular service or route',
            ADD COLUMN CarNumber VARCHAR(20) COMMENT 'Train car number',
            ADD COLUMN AdultsCount INT DEFAULT NULL,
            ADD COLUMN KidsCount INT DEFAULT NULL,
            ADD COLUMN WetLeaseAirlineID INT(11) DEFAULT NULL,
            ADD CONSTRAINT fk_trip_segment_wet_lease_airline FOREIGN KEY fk_trip_segment_wet_lease_airline (WetLeaseAirlineID) REFERENCES Airline (AirlineID),
            ADD COLUMN WetLeaseAirlineName VARCHAR(100) DEFAULT NULL,
            ADD COLUMN ParsedStatus VARCHAR(20) DEFAULT NULL
        ");
        $this->addSql("SET FOREIGN_KEY_CHECKS=1");
    }

    public function down(Schema $schema): void
    {
        $this->addSql("
            ALTER TABLE TripSegment
            DROP COLUMN ParsedStatus,
            DROP FOREIGN KEY fk_trip_segment_wet_lease_airline,
            DROP COLUMN WetLeaseAirlineID,
            DROP COLUMN WetLeaseAirlineName, 
            DROP COLUMN CarNumber,
            DROP COLUMN AdultsCount,
            DROP COLUMN KidsCount,
            DROP COLUMN ServiceName,
            DROP COLUMN OperatingAirlinePhoneNumbers,
            DROP COLUMN MarketingAirlinePhoneNumbers,
            DROP COLUMN OperatingAirlineConfirmationNumber,
            DROP COLUMN MarketingAirlineConfirmationNumber,
            CHANGE AirlineName AirlineName VARCHAR(250) DEFAULT NULL COMMENT 'Название компании совершающей рейс',
            CHANGE AirlineID AirlineID INT(11) DEFAULT NULL COMMENT '',
            DROP COLUMN ServiceClasses,
            DROP COLUMN OperatingAirlineFlightNumber,
            DROP COLUMN OperatingAirlineName,
            DROP FOREIGN KEY fk_trip_segment_operating_airline,
            DROP COLUMN OperatingAirlineID
       ");

        $this->addSql("
            ALTER TABLE Trip 
              DROP FOREIGN KEY fk_trip_issuing_airline,
              DROP COLUMN AirlineID,
              DROP COLUMN ParsedStatus,
              DROP COLUMN TicketNumbers,
              DROP COLUMN TravelAgencyParsedAccountNumbers,
              DROP FOREIGN KEY fk_trip_travel_agency, 
              DROP INDEX fk_trip_travel_agency, 
              DROP COLUMN TravelAgencyID
         ");
        $this->addSql("
            ALTER TABLE Reservation
            DROP COLUMN ParsedStatus,
            DROP COLUMN TravelAgencyParsedAccountNumbers, 
            DROP FOREIGN KEY fk_reservation_travel_agency, 
            DROP INDEX fk_reservation_travel_agency, 
            DROP COLUMN TravelAgencyID
        ");
        $this->addSql("
            ALTER TABLE Rental
            DROP COLUMN RentalCompanyName,
            DROP COLUMN ParsedStatus,
            DROP COLUMN TravelAgencyParsedAccountNumbers, 
            DROP FOREIGN KEY fk_rental_travel_agency, 
            DROP INDEX fk_rental_travel_agency, 
            DROP COLUMN TravelAgencyID
        ");
        $this->addSql("
          ALTER TABLE Restaurant
            DROP COLUMN ParsedStatus,
            DROP COLUMN Fax,
            DROP COLUMN TravelAgencyParsedAccountNumbers, 
            DROP FOREIGN KEY fk_event_travel_agency, 
            DROP INDEX fk_event_travel_agency, 
            DROP COLUMN TravelAgencyID,
            DROP COLUMN GuestCount
         ");
    }
}
