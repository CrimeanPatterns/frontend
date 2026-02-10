<?php

namespace AwardWallet\MainBundle\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

class Version20151007075031 extends AbstractMigration
{
    public function up(Schema $schema): void
    {
        $this->addSql("create table UserIP(
            UserIPID int not null auto_increment,
            UserID int not null,
            IP varchar(15) not null,
            UpdateDate timestamp not null,
            primary key(UserIPID),
            foreign key(UserID) references Usr(UserID) on delete cascade,
            unique key(UserID, IP)
        ) engine=InnoDB comment 'Последние 100 IP с которых логинился пользователь'");
    }

    public function down(Schema $schema): void
    {
        $this->addSql("drop table UserIP");
    }
}
