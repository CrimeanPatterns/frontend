<?php

namespace AwardWallet\MainBundle\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your need!
 */
class Version20130705231628 extends AbstractMigration
{
    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql("
            create table TravelPlanNew(
            TravelPlanNewID int not null auto_increment,
            Hidden tinyint not null default 0,
            UserID int not null,
            UserAgentID int,
            Name varchar(250) not null,
            Code varchar(20),
            PictureVer int,
            PictureExt varchar(5),
            Public int not null default 1,
            CustomDates tinyint not null default 0,
            CustomName tinyint not null default 0,
            primary key( TravelPlanNewID ),
            foreign key(UserAgentID) references UserAgent(UserAgentID) on delete set null,
            foreign key( UserID ) references Usr( UserID ) on delete cascade
            )
        ");
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql("
            drop table TravelPlanNew;
        ");
    }
}
