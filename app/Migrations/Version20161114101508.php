<?php

namespace AwardWallet\MainBundle\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

class Version20161114101508 extends AbstractMigration
{
    public function up(Schema $schema): void
    {
        $this->addSql("
            create table `FlightInfoLog`(
                `FlightInfoLogID` int not null auto_increment,
                `Service` varchar(100) not null,
                `State` tinyint(1) not null default '0',
                `Changed` tinyint(1) not null default '0',
                `Request` varchar(1000) not null,
                `Response` text,
                `CreateDate` datetime not null,
                `ExpireDate` datetime,
                primary key(`FlightInfoLogID`)
            );
        ");
    }

    public function down(Schema $schema): void
    {
        $this->addSql("DROP TABLE `FlightInfoLog`");
    }
}
