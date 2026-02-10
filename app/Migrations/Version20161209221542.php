<?php

namespace AwardWallet\MainBundle\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

class Version20161209221542 extends AbstractMigration
{
    public function up(Schema $schema): void
    {
        $this->addSql("
            create table `FlightInfoConfig`(
                `FlightInfoConfigID` int not null auto_increment,
                `Name` varchar(100) not null,
                `Type` tinyint(3) not null default '0',
                `Service` varchar(100) not null,
                `Comment` varchar(1000) not null,
                `ScheduleRules` varchar(1000) not null,
                `IgnoreFields` varchar(1000) not null,
                `Enable` tinyint(1) not null default '0',
                `Schedule` tinyint(1) not null default '0',
                `Debug` tinyint(1) not null default '0',
                `AWPlusFlag` tinyint(1) not null default '0',
                primary key(`FlightInfoConfigID`)
            );
        ");
    }

    public function down(Schema $schema): void
    {
        $this->addSql("DROP TABLE `FlightInfoConfig`");
    }
}
