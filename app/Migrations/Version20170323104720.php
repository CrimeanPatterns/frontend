<?php

namespace AwardWallet\MainBundle\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
class Version20170323104720 extends AbstractMigration
{
    public function up(Schema $schema): void
    {
        $this->addSql('drop table if exists `UserTripTargeting`');
        $this->addSql("
            create table `UserTripTargeting` (
                `UserTripTargetingID` int(11) not null auto_increment,
                `UserID` int(11) not null comment 'пользователь',
                `DestinationAirport` varchar(10) not null comment 'буквенный код аэропорта куда летал пользователь',
                `LastOriginAirport` varchar(10) not null comment 'буквенный код аэропорта откуда последний раз летал пользователь',
                `TimesTraveled` int(11) not null comment 'сколько раз пользователь летал в это место',
                `LastTimeTraveledToDestination` datetime not null comment 'когда пользователь в последний раз летал в это место',
                
                primary key (`UserTripTargetingID`),
                unique key `idx_UserTripTargeting_UserID_DestinationAirport` (`UserID`, `DestinationAirport`),
                key `idx_UserTripTargeting_LastTimeTraveledToDestination` (`LastTimeTraveledToDestination`),
                foreign key(`UserID`) references Usr(`UserID`) on delete cascade
            ) engine=InnoDB comment='статистика полетов пользователей'"
        );
    }

    public function down(Schema $schema): void
    {
    }
}
