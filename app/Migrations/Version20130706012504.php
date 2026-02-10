<?php

namespace AwardWallet\MainBundle\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your need!
 */
class Version20130706012504 extends AbstractMigration
{
    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql("
            create table TravelPlanNewSection(
            TravelPlanNewSectionID int unsigned not null auto_increment,
            TravelPlanNewID int not null,
            SectionKind char(1),
            SectionID int,
            primary key( TravelPlanNewSectionID ),
            foreign key( TravelPlanNewID ) references TravelPlanNew( TravelPlanNewID ) on delete cascade
            )
            ENGINE=InnoDB DEFAULT CHARSET=utf8;
        ");
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql("drop table TravelPlanNewSection");
    }
}
