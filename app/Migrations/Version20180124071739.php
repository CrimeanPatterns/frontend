<?php

namespace AwardWallet\MainBundle\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
class Version20180124071739 extends AbstractMigration
{
    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE TripSegment DROP FOREIGN KEY fk_TripSegment_ref_Airline');
        $this->addSql('ALTER TABLE TripSegment ADD CONSTRAINT fk_TripSegment_ref_Airline FOREIGN KEY (AirlineID) REFERENCES Airline(AirlineID) ON DELETE SET NULL');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE TripSegment DROP FOREIGN KEY fk_TripSegment_ref_Airline');
        $this->addSql('ALTER TABLE TripSegment ADD CONSTRAINT fk_TripSegment_ref_Airline FOREIGN KEY (AirlineID) REFERENCES Airline(AirlineID)');
    }
}
