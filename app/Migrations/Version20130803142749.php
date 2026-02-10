<?php

namespace AwardWallet\MainBundle\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your need!
 */
class Version20130803142749 extends AbstractMigration
{
    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql("alter table Reservation add ShareCode varchar(32) default null");
        $this->addSql("alter table Rental add ShareCode varchar(32) default null");
        $this->addSql("alter table TripSegment add ShareCode varchar(32) default null");
        $this->addSql("alter table Restaurant add ShareCode varchar(32) default null");
        $this->addSql("alter table Direction add ShareCode varchar(32) default null");
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql("alter table Direction drop ShareCode");
        $this->addSql("alter table Restaurant drop ShareCode");
        $this->addSql("alter table TripSegment drop ShareCode");
        $this->addSql("alter table Rental drop ShareCode");
        $this->addSql("alter table Reservation drop ShareCode");
    }
}
