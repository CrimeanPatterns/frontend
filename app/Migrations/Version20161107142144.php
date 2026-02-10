<?php

namespace AwardWallet\MainBundle\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

class Version20161107142144 extends AbstractMigration
{
    public function up(Schema $schema): void
    {
        $this->addSql("
            create table `AirlineAlias`(
                `AirlineAliasID` int not null auto_increment,
                `AirlineID` int not null,
                `Alias` varchar(250) not null,
                `LastUpdateDate` datetime,
                primary key(`AirlineAliasID`),
                foreign key(`AirlineID`) references Airline(`AirlineID`) on delete cascade
            );
        ");
    }

    public function down(Schema $schema): void
    {
        $this->addSql("DROP TABLE `AirlineAlias`");
    }
}
