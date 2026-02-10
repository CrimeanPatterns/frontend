<?php

namespace AwardWallet\MainBundle\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your need!
 */
class Version20130718130239 extends AbstractMigration
{
    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        // in case "Column 'TravelPlanID' in field list is ambiguous" error occurs
        // this is probably because of this migration
        $this->addSql("alter table TripSegment add TravelPlanID int");
        $this->addSql("alter table TripSegment add foreign key( TravelPlanID ) references TravelPlan( TravelPlanID ) on delete cascade");
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        //check if this constraint is actually called that
        $this->addSql("alter table TripSegment drop foreign key TripSegment_ibfk_1");
        $this->addSql("alter table TripSegment drop column TravelPlanID");
    }
}
