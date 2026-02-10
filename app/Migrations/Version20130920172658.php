<?php

namespace AwardWallet\MainBundle\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your need!
 */
class Version20130920172658 extends AbstractMigration
{
    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql("create table Visit (
            VisitID int unsigned not null auto_increment,
            UserID int not null,
            VisitDate date not null,
            Visits int default 1,
            PRIMARY KEY (VisitID),
            unique (UserID, VisitDate)
        )
        ENGINE=InnoDB DEFAULT CHARSET=utf8");
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql("drop table Visit");
    }
}
