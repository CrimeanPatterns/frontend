<?php

namespace AwardWallet\MainBundle\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
class Version20171213103123 extends AbstractMigration
{
    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE TripSegment ADD COLUMN AirlineID INT DEFAULT NULL AFTER AirlineName');
        $this->addSql('ALTER TABLE TripSegment ADD CONSTRAINT fk_TripSegment_ref_Airline FOREIGN KEY (AirlineID) REFERENCES Airline(AirlineID)');
        $this->addSql('UPDATE TripSegment LEFT JOIN Airline ON TripSegment.AirlineName = Airline.Name SET TripSegment.AirlineID = Airline.AirlineID');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE TripSegment DROP FOREIGN KEY fk_TripSegment_ref_Airline');
        $this->addSql('ALTER TABLE TripSegment DROP COLUMN AirlineID');
    }
}
