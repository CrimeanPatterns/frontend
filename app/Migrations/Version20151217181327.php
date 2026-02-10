<?php

namespace AwardWallet\MainBundle\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

class Version20151217181327 extends AbstractMigration
{
    public function up(Schema $schema): void
    {
        $this->addSql("
			create table `FlightInfo`(
				`FlightInfoID` int not null auto_increment,
				`ProviderID` int not null,
				`FlightNumber` varchar(20) not null,
				`FlightDate` datetime not null,
				`CreateDate` datetime not null,
				`UpdateDate` datetime not null comment 'Когда информацию достали из FlightStats API',
				`Properties` text comment 'Свойства, которые мы получили от FlightStats, массив PropertyCode=Value сериализованный в JSON',
				`UpdatesCount` int not null default 1 comment 'Сколько раз мы вызывали API для этого полета, для финансовой статистики',
				primary key(`FlightInfoID`),
				foreign key(`ProviderID`) references Provider(`ProviderID`) on delete cascade,
				unique key(`ProviderID`, `FlightNumber`, `FlightDate`),
				index (`FlightDate`)
			) engine=InnoDB comment='Кэшируемая информация из FlightStats API';
		");

        $this->addSql("alter table `TripSegment` add `FlightInfoID` int;");
        $this->addSql("alter table `TripSegment` add CONSTRAINT FlightInfoID foreign key(FlightInfoID) references FlightInfo(FlightInfoID) on delete set null;");
    }

    public function down(Schema $schema): void
    {
        $this->addSql("alter table `TripSegment` drop foreign key FlightInfoID");
        $this->addSql("alter table `TripSegment` drop column `FlightInfoID`");
        $this->addSql("DROP TABLE `FlightInfo`");
    }
}
