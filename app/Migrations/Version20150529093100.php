<?php

namespace AwardWallet\MainBundle\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
class Version20150529093100 extends AbstractMigration
{
    public function up(Schema $schema): void
    {
        $this->addSql("update Trip, TravelPlan set Trip.Hidden = 1 where Trip.TravelPlanID = TravelPlan.TravelPlanID and TravelPlan.Hidden = 1");
        $this->addSql("update Rental, TravelPlan set Rental.Hidden = 1 where Rental.TravelPlanID = TravelPlan.TravelPlanID and TravelPlan.Hidden = 1");
        $this->addSql("update Reservation, TravelPlan set Reservation.Hidden = 1 where Reservation.TravelPlanID = TravelPlan.TravelPlanID and TravelPlan.Hidden = 1");
        $this->addSql("update Restaurant, TravelPlan set Restaurant.Hidden = 1 where Restaurant.TravelPlanID = TravelPlan.TravelPlanID and TravelPlan.Hidden = 1");
        $this->addSql("update Direction, TravelPlan set Direction.Hidden = 1 where Direction.TravelPlanID = TravelPlan.TravelPlanID and TravelPlan.Hidden = 1");
    }

    public function down(Schema $schema): void
    {
    }
}
