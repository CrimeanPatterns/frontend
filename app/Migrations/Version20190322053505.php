<?php

declare(strict_types=1);

namespace AwardWallet\MainBundle\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20190322053505 extends AbstractMigration
{
    public function up(Schema $schema): void
    {
        $this->addSql("create table HotelPointValue(
            HotelPointValueID int not null auto_increment,
            ProviderID int not null,
            ReservationID int,
            HotelName varchar(250) not null,
            Address varchar(250),
            LatLng varchar(80),
            CheckInDate date not null,
            CheckOutDate date not null,
            GuestCount tinyint not null,
            KidsCount tinyint not null,
            RoomCount tinyint not null,
            Hash varchar(32) NOT NULL,
            CreateDate datetime NOT NULL,
            UpdateDate datetime NOT NULL,
            TotalPointsSpent decimal(10,2) NOT NULL,
            TotalTaxesSpent decimal(10,2) NOT NULL,
            
            AlternativeHotelName varchar(250) not null,
            AlternativeHotelURL varchar(512) not null,
            AlternativeBookingURL varchar(512) not null,
            AlternativeLatLng varchar(80),
            AlternativeCost decimal(10,2) NOT NULL,
            PointValue decimal(10,4) NOT NULL,
            
            Status char(1) NOT NULL DEFAULT 'N' COMMENT 'see CalcHotelPointValueCommand::STATUSES',
            Note varchar(500) DEFAULT NULL COMMENT 'User-entered note',
            
            primary key (HotelPointValueID),
            unique key (ReservationID),
            foreign key (ProviderID) references Provider(ProviderID) on delete cascade,
            foreign key (ReservationID) references Reservation(ReservationID) on delete set null
        ) engine=InnoDB");
    }

    public function down(Schema $schema): void
    {
        $this->addSql("drop table HotelPointValue");
    }
}
