<?php

namespace AwardWallet\MainBundle\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

class Version20151113121201 extends AbstractMigration
{
    public function up(Schema $schema): void
    {
        $this->addSql("ALTER TABLE Usr ADD Language varchar(3) not null default 'en' COMMENT 'Язык интерфейса'");
        $this->addSql("ALTER TABLE Usr ADD Region varchar(10) default null COMMENT 'Регион'");
    }

    public function down(Schema $schema): void
    {
        $this->addSql("alter table Usr drop Language");
        $this->addSql("alter table Usr drop Region");
    }
}
