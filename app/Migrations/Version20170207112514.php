<?php

namespace AwardWallet\MainBundle\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

class Version20170207112514 extends AbstractMigration
{
    public function up(Schema $schema): void
    {
        $this->addSql("
			create table `TripInfo`(
				`TripInfoID` int not null auto_increment,
				`UserID` int not null,
				`Mode` tinyint not null,
				`State` tinyint not null,
				`StartCode` varchar(20) not null comment 'Код аэропорта начала путешествия',
				`StartDate` datetime not null,
				`EndCode` varchar(20) not null comment 'Код аэропорта окончания путешествия',
				`EndDate` datetime not null,
				`SyncDate` datetime comment 'Дата последней синхронизации с FlightStats',
				primary key(`TripInfoID`),
				foreign key(`UserID`) references Usr(`UserID`) on delete cascade,
				index (`StartDate`)
			) engine=InnoDB;
		");

        $this->addSql("alter table `TripSegment` add `TripInfoID` int;");
        $this->addSql("alter table `TripSegment` add CONSTRAINT TripInfoID foreign key(TripInfoID) references TripInfo(TripInfoID) on delete set null;");
    }

    public function down(Schema $schema): void
    {
        $this->addSql("alter table `TripSegment` drop foreign key TripInfoID");
        $this->addSql("alter table `TripSegment` drop column `TripInfoID`");
        $this->addSql("DROP TABLE `TripInfo`");
    }
}
