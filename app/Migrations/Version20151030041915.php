<?php

namespace AwardWallet\MainBundle\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

class Version20151030041915 extends AbstractMigration
{
    public function up(Schema $schema): void
    {
        $this->addSql("create table OneTimeCode(
            OneTimeCodeID int not null auto_increment,
            UserID int not null,
            Code char(6) not null comment '6 цифр',
            CreationDate timestamp,
            primary key (OneTimeCodeID),
            foreign key fkUser (UserID) references Usr(UserID) on delete cascade,
            unique key akCode(UserID, Code)
        ) engine=InnoDB comment 'OTC коды высылаемые на email пользователя в момент логина при незнакомом IP'");
    }

    public function down(Schema $schema): void
    {
        $this->addSql("drop table OneTimeCode");
    }
}
