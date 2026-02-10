<?php

declare(strict_types=1);

namespace AwardWallet\MainBundle\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20190301124018 extends AbstractMigration
{
    public function up(Schema $schema): void
    {
        $this->addSql("create table MileValue(
            MileValueID int not null auto_increment,
            ProviderID int not null, 
            MileAirlines varchar(40),
            CashAirlines varchar(250),
            TripID int, 
            Route varchar(20) not null,
            International tinyint not null,
            MileRoute varchar(80) not null,
            CashRoute varchar(80) not null,
            BookingClasses varchar(20) not null,
            CabinClass varchar(40) not null,
            ClassOfService varchar(40) not null,
            DepDate datetime not null,
            ReturnDate datetime,
            MileDuration decimal(4,1) not null,
            CashDuration decimal(4,1) not null,
            Hash varchar(32) not null,
            CreateDate datetime not null, 
            UpdateDate datetime not null, 
            TotalMilesSpent decimal(10,2) not null, 
            TotalTaxesSpent decimal(10,2) not null, 
            AlternativeCost decimal(10,2) not null, 
            MileValue decimal(10,4) not null,
            primary key (MileValueID),
            unique key akTrip(TripID),
            foreign key fkTrip(TripID) references Trip(TripID) on delete set null,
            foreign key fkProvider(ProviderID) references Provider(ProviderID) on delete cascade 
        ) engine=InnoDb");
    }

    public function down(Schema $schema): void
    {
        $this->addSql("drop table MileValue");
    }
}
