<?php

namespace AwardWallet\MainBundle\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * drop travel confirm tables.
 */
class Version20131129122513 extends AbstractMigration
{
    public function up(Schema $schema): void
    {
        $this->addSql('drop table ItineraryOffer');
        $this->addSql('drop table ItineraryHistory');
        $this->addSql('drop table HotelBookingOfferDetail');
        $this->addSql('drop table HotelBookingOffer');

        $this->addSql('alter table Flights
			drop AllowChangelessSavings,
			drop CouponCode1,
			drop CouponCode2,
			drop LastOfferDate,
			drop ProcessedByTC,
			drop SavingsAmount,
			drop SavingsConfirmed,
			drop UserPermission');

        $this->addSql('alter table Rental
			drop AllowChangelessSavings,
			drop CouponCode1,
			drop CouponCode2,
			drop LastOfferDate,
			drop ProcessedByTC,
			drop SavingsAmount,
			drop SavingsConfirmed,
			drop UserPermission');

        $this->addSql('alter table Reservation
			drop AllowChangelessSavings,
			drop CouponCode1,
			drop CouponCode2,
			drop LastOfferDate,
			drop ProcessedByTC,
			drop SavingsAmount,
			drop SavingsConfirmed,
			drop TCLastTryDate,
			drop TCRetries,
			drop TCSucessAddDate,
			drop UserPermission');

        $this->addSql('alter table Restaurant drop UserPermission');

        $this->addSql('alter table Trip
			drop AllowChangelessSavings,
			drop CouponCode1,
			drop CouponCode2,
			drop LastOfferDate,
			drop ProcessedByTC,
			drop SavingsAmount,
			drop SavingsConfirmed,
			drop UserPermission');
    }

    public function down(Schema $schema): void
    {
    }
}
