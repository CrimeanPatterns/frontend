<?php

namespace AwardWallet\MainBundle\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

class Version20151130113457 extends AbstractMigration
{
    public function up(Schema $schema): void
    {
        $this->addSql("create table Plan(
            PlanID integer not null auto_increment,
            UserID int not null,
            UserAgentID int,
            Name varchar(250) not null,
            CreationDate datetime not null,
            StartDate datetime not null,
            EndDate datetime not null,
            primary key(PlanID),
            foreign key(UserID) references Usr(UserID) on delete cascade,
            foreign key(UserAgentID) references UserAgent(UserAgentID) on delete cascade
        ) engine=InnoDB");
    }

    public function down(Schema $schema): void
    {
        $this->addSql("drop table Plan");
    }
}
