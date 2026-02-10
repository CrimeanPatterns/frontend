<?php

namespace AwardWallet\MainBundle\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your need!
 */
class Version20130712133100 extends AbstractMigration
{
    public function up(Schema $schema): void
    {
        $this->addSql("alter table Cart modify Code varchar(20)");
        $this->addSql("alter table Cart modify FirstName varchar(40)");
        $this->addSql("alter table Cart modify LastName varchar(40)");
        $this->addSql("alter table Cart modify Processed tinyint not null default 0");
        $this->addSql("alter table CartItem modify Discount int not null default 0");
    }

    public function down(Schema $schema): void
    {
        $this->addSql("alter table Cart modify Code varchar(20) not null");
        $this->addSql("alter table Cart modify FirstName varchar(40) not null");
        $this->addSql("alter table Cart modify LastName varchar(40) not null");
        $this->addSql("alter table Cart modify Processed tinyint not null");
        $this->addSql("alter table CartItem modify Discount int not null");
    }
}
