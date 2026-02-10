<?php

namespace AwardWallet\MainBundle\Migrations;

use Doctrine\DBAL\Migrations\IrreversibleMigrationException;
use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Create Entity fields to accommodate ExtProperties.
 */
class Version20180405080702 extends AbstractMigration
{
    public function up(Schema $schema): void
    {
        $this->addSql("
            ALTER TABLE TripSegment
            ADD COLUMN AircraftID INT NULL COMMENT 'Association with Aircraft',
            ADD COLUMN Aircraft VARCHAR(100) NULL COMMENT 'Aircraft name as parsed',
            ADD COLUMN ArrivalGate VARCHAR(10) NULL COMMENT 'e.g. \"12\", \"B25\"',
            ADD COLUMN DepartureGate VARCHAR(10) NULL COMMENT 'e.g. \"12\", \"B25\"',
            ADD COLUMN ArrivalTerminal VARCHAR(50) NULL COMMENT 'e.g. \"1\", \"A\", \"Terminal B\"',
            ADD COLUMN DepartureTerminal VARCHAR(50) NULL COMMENT 'e.g. \"1\", \"A\", \"Terminal B\"',
            ADD COLUMN BaggageClaim VARCHAR(10) NULL COMMENT 'e.g. \"A16\", \"F\", \"3\"',
            ADD COLUMN BookingClass VARCHAR(2) NULL COMMENT 'Booking class code, i.e. \"T\"',
            ADD COLUMN CabinClass VARCHAR(25) NULL COMMENT 'Cabin class, i.e. \"Economy\"',
            ADD COLUMN Duration VARCHAR(15) NULL COMMENT 'Duration as parsed (mostly something like \"5h 32m\")',
            ADD COLUMN Smoking BOOL NULL,
            ADD COLUMN Stops TINYINT NULL COMMENT 'Stops count',
            ADD COLUMN TraveledMiles VARCHAR(25) NULL COMMENT 'Number of miles traveled on this flight segment. As parsed.',
            ADD COLUMN Meal VARCHAR(50) NULL COMMENT 'Arbitrary format',
            ADD COLUMN Seats TINYTEXT NULL COMMENT 'Simple Array format (e.g. \"19E,19F,19D\")',
            ADD COLUMN ExtPropertyMerged BOOL DEFAULT FALSE COMMENT 'Temporary field, remove with ExtProperty table',
            ADD CONSTRAINT fk_trip_segment_aircraft FOREIGN KEY idx_trip_segment_aircraft (AircraftID) REFERENCES Aircraft (AircraftID) ON DELETE SET NULL 
        ");
        $this->addSql("
            ALTER TABLE Trip
            ADD COLUMN ConfirmationNumbers TINYTEXT NULL COMMENT 'Simple Array format (e.g. \"KMGOFA,G7IILB\")',
            ADD COLUMN TravelAgencyConfirmationNumbers TINYTEXT NULL COMMENT 'Simple Array format (e.g. \"KMGOFA,G7IILB\")',
            ADD COLUMN ParsedAccountNumbers TINYTEXT NULL COMMENT 'Parsed account numbers in Simple Array format (e.g. \"80050130,49304756\")',
            ADD COLUMN Cost DECIMAL(12, 2) NULL COMMENT 'Cost before taxes',
            ADD COLUMN CurrencyCode VARCHAR(5) NULL COMMENT 'Currency code as gathered from the website (e.g. \"USD\")',
            ADD COLUMN Discount DECIMAL(12, 2) NULL COMMENT 'Total amount of discounts, if any.',
            ADD COLUMN Fees TEXT NULL COMMENT 'Fees as a serialized array of \\\AwardWallet\\\MainBundle\\\Entity\\\Fee objects',
            ADD COLUMN Tax DECIMAL(12, 2) NULL COMMENT 'Amount paid in taxes',
            ADD COLUMN Total DECIMAL(12, 2) NULL COMMENT 'Total cost of the reservation including all taxes and fees',
            ADD COLUMN SpentAwards VARCHAR(50) NULL COMMENT 'Frequent flier miles, points, or other kinds or bonuses spent on this reservation',
            ADD COLUMN EarnedAwards VARCHAR(50) NULL COMMENT 'Rewards earned for booking the reservation',
            ADD COLUMN TravelAgencyEarnedAwards VARCHAR(50) NULL COMMENT 'Rewards earned for booking the reservation',
            ADD COLUMN ReservationDate DATETIME NULL COMMENT 'Date when the reservation was booked',
            ADD COLUMN TravelerNames TINYTEXT NULL COMMENT 'Traveler names as Simple Array (e.g. \"John Doe,Melissa Doe\")',
            ADD COLUMN CruiseName VARCHAR(100) NULL COMMENT 'Cruise description, such as \"7-Day Eastern Caribbean from Orlando (Port Canaveral)\"',
            ADD COLUMN Deck VARCHAR(25) NULL COMMENT 'Name of the deck, e.g. \"The Haven Stateroom\"',
            ADD COLUMN CabinClass VARCHAR(50) NULL COMMENT 'e.g. \"Deluxe Ocean View Strm Veranda\"',
            ADD COLUMN CabinNumber VARCHAR(10) NULL COMMENT 'e.g. \"D212\"',
            ADD COLUMN ShipCode VARCHAR(2) NULL COMMENT 'This is usually a two-letter code that identifies the ship',
            ADD COLUMN ShipName VARCHAR(25) NULL COMMENT 'The name of the ship, i.e. \"Norwegian Epic\"',
            ADD COLUMN ExtPropertyMerged BOOL DEFAULT FALSE COMMENT 'Temporary field, remove with ExtProperty table'
        ");
        $this->addSql("
            ALTER TABLE Reservation
            ADD COLUMN ConfirmationNumbers TINYTEXT NULL COMMENT 'Simple Array format (e.g. \"KMGOFA,G7IILB\")',
            ADD COLUMN TravelAgencyConfirmationNumbers TINYTEXT NULL COMMENT 'Simple Array format (e.g. \"KMGOFA,G7IILB\")',
            ADD COLUMN ParsedAccountNumbers TINYTEXT NULL COMMENT 'Parsed account numbers in Simple Array format (e.g. \"80050130,49304756\")',
            ADD COLUMN Cost DECIMAL(12, 2) NULL COMMENT 'Cost before taxes',
            ADD COLUMN CurrencyCode VARCHAR(5) NULL COMMENT 'Currency code as gathered from the website (e.g. \"USD\")',
            ADD COLUMN Discount DECIMAL(12, 2) NULL COMMENT 'Total amount of discounts, if any.',
            ADD COLUMN Fees TEXT NULL COMMENT 'Fees as a serialized array of \\\AwardWallet\\\MainBundle\\\Entity\\\Fee objects',
            ADD COLUMN Tax DECIMAL(12, 2) NULL COMMENT 'Amount paid in taxes',
            ADD COLUMN Total DECIMAL(12, 2) NULL COMMENT 'Total cost of the reservation including all taxes and fees',
            ADD COLUMN SpentAwards VARCHAR(50) NULL COMMENT 'Frequent flier miles, points, or other kinds or bonuses spent on this reservation',
            ADD COLUMN EarnedAwards VARCHAR(50) NULL COMMENT 'Rewards earned for booking the reservation',
            ADD COLUMN TravelAgencyEarnedAwards VARCHAR(50) NULL COMMENT 'Rewards earned for booking the reservation',
            ADD COLUMN ReservationDate DATETIME NULL COMMENT 'Date when the reservation was booked',
            ADD COLUMN TravelerNames TINYTEXT NULL COMMENT 'Traveler names as Simple Array (e.g. \"John Doe,Melissa Doe\")',
            ADD COLUMN CancellationPolicy TEXT NULL,
            ADD COLUMN Fax VARCHAR(25) NULL,
            ADD COLUMN GuestCount TINYINT NULL,
            ADD COLUMN KidsCount TINYINT NULL,
            ADD COLUMN Rooms TEXT NULL COMMENT 'Rooms as serialized array of \\\AwardWallet\\\MainBundle\\\Entity\\\Room objects',
            ADD COLUMN RoomCount TINYINT NULL,
            ADD COLUMN ExtPropertyMerged BOOL DEFAULT FALSE COMMENT 'Temporary field, remove with ExtProperty table'
        ");
        $this->addSql("
            ALTER TABLE Rental
            ADD COLUMN ConfirmationNumbers TINYTEXT NULL COMMENT 'Simple Array format (e.g. \"KMGOFA,G7IILB\")',
            ADD COLUMN TravelAgencyConfirmationNumbers TINYTEXT NULL COMMENT 'Simple Array format (e.g. \"KMGOFA,G7IILB\")',
            ADD COLUMN ParsedAccountNumbers TINYTEXT NULL COMMENT 'Parsed account numbers in Simple Array format (e.g. \"80050130,49304756\")',
            ADD COLUMN Cost DECIMAL(12, 2) NULL COMMENT 'Cost before taxes',
            ADD COLUMN CurrencyCode VARCHAR(5) NULL COMMENT 'Currency code as gathered from the website (e.g. \"USD\")',
            ADD COLUMN Discount DECIMAL(12, 2) NULL COMMENT 'Total amount of discounts, if any.',
            ADD COLUMN Fees TEXT NULL COMMENT 'Fees as a serialized array of \\\AwardWallet\\\MainBundle\\\Entity\\\Fee objects',
            ADD COLUMN Tax DECIMAL(12, 2) NULL COMMENT 'Amount paid in taxes',
            ADD COLUMN Total DECIMAL(12, 2) NULL COMMENT 'Total cost of the reservation including all taxes and fees',
            ADD COLUMN SpentAwards VARCHAR(50) NULL COMMENT 'Frequent flier miles, points, or other kinds or bonuses spent on this reservation',
            ADD COLUMN EarnedAwards VARCHAR(50) NULL COMMENT 'Rewards earned for booking the reservation',
            ADD COLUMN TravelAgencyEarnedAwards VARCHAR(50) NULL COMMENT 'Rewards earned for booking the reservation',
            ADD COLUMN ReservationDate DATETIME NULL COMMENT 'Date when the reservation was booked',
            ADD COLUMN TravelerNames TINYTEXT NULL COMMENT 'Traveler names as Simple Array (e.g. \"John Doe,Melissa Doe\")',
            ADD COLUMN CarImageUrl VARCHAR(255) NULL COMMENT 'URL of the image of the car',
            ADD COLUMN CarModel VARCHAR(100) NULL COMMENT 'Model of the rental car, e.g. \"Ford Escape or similar\"',
            ADD COLUMN CarType VARCHAR(100) NULL ,
            ADD COLUMN PickUpFax VARCHAR(25) NULL,
            ADD COLUMN DropOffFax VARCHAR(25) NULL,
            ADD COLUMN ExtPropertyMerged BOOL DEFAULT FALSE COMMENT 'Temporary field, remove with ExtProperty table'
        ");
        $this->addSql("
            ALTER TABLE Restaurant
            ADD COLUMN ConfirmationNumbers TINYTEXT NULL COMMENT 'Simple Array format (e.g. \"KMGOFA,G7IILB\")',
            ADD COLUMN TravelAgencyConfirmationNumbers TINYTEXT NULL COMMENT 'Simple Array format (e.g. \"KMGOFA,G7IILB\")',
            ADD COLUMN ParsedAccountNumbers TINYTEXT NULL COMMENT 'Parsed account numbers in Simple Array format (e.g. \"80050130,49304756\")',
            ADD COLUMN Cost DECIMAL(12, 2) NULL COMMENT 'Cost before taxes',
            ADD COLUMN CurrencyCode VARCHAR(5) NULL COMMENT 'Currency code as gathered from the website (e.g. \"USD\")',
            ADD COLUMN Discount DECIMAL(12, 2) NULL COMMENT 'Total amount of discounts, if any.',
            ADD COLUMN Fees TEXT NULL COMMENT 'Fees as a serialized array of \\\AwardWallet\\\MainBundle\\\Entity\\\Fee objects',
            ADD COLUMN Tax DECIMAL(12, 2) NULL COMMENT 'Amount paid in taxes',
            ADD COLUMN Total DECIMAL(12, 2) NULL COMMENT 'Total cost of the reservation including all taxes and fees',
            ADD COLUMN SpentAwards VARCHAR(50) NULL COMMENT 'Frequent flier miles, points, or other kinds or bonuses spent on this reservation',
            ADD COLUMN EarnedAwards VARCHAR(50) NULL COMMENT 'Rewards earned for booking the reservation',
            ADD COLUMN TravelAgencyEarnedAwards VARCHAR(50) NULL COMMENT 'Rewards earned for booking the reservation',
            ADD COLUMN ReservationDate DATETIME NULL COMMENT 'Date when the reservation was booked',
            ADD COLUMN TravelerNames TINYTEXT NULL COMMENT 'Traveler names as Simple Array (e.g. \"John Doe,Melissa Doe\")',
            ADD COLUMN ExtPropertyMerged BOOL DEFAULT FALSE COMMENT 'Temporary field, remove with ExtProperty table'
        ");
        $this->addSql("CREATE INDEX idx_trip_segment_ext_property_merged ON TripSegment (ExtPropertyMerged)");
        $this->addSql("CREATE INDEX idx_trip_ext_property_merged ON Trip (ExtPropertyMerged)");
        $this->addSql("CREATE INDEX idx_reservation_ext_property_merged ON Reservation (ExtPropertyMerged)");
        $this->addSql("CREATE INDEX idx_rental_ext_property_merged ON Rental (ExtPropertyMerged)");
        $this->addSql("CREATE INDEX idx_restaurant_ext_property_merged ON Restaurant (ExtPropertyMerged)");
    }

    /**
     * @throws IrreversibleMigrationException
     */
    public function down(Schema $schema): void
    {
        $this->throwIrreversibleMigrationException();
    }
}
