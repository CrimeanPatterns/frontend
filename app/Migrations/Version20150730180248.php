<?php

namespace AwardWallet\MainBundle\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

class Version20150730180248 extends AbstractMigration
{
    public function up(Schema $schema): void
    {
        $this->addSql("
        create table BotIP(
        	IP varchar(20) not null,
        	AddDate datetime not null,
        	UpdateDate datetime not null,
        	Attempts int not null,
        	Source tinyint,
        	primary key(IP)
        ) engine=InnoDB comment 'IP ботов'
        ");
    }

    public function down(Schema $schema): void
    {
        $this->addSql("drop table BotIP");
    }
}
