<?php

namespace AwardWallet\MainBundle\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your need!
 */
class Version20130618193606 extends AbstractMigration
{
    public function up(Schema $schema): void
    {
        $this->addSql("
            create table OfferRocketmilesShown (
                OfferRocketmilesShownID int(11) unsigned not null auto_increment,
                UserID int(11) unsigned not null,
                RecordID int(11) unsigned not null,
                RecordType char(1) not null,
                PRIMARY KEY (OfferRocketmilesShownID),
                unique (UserID, RecordID, RecordType)
            )
        ");
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql("drop table OfferRocketmilesShown");
    }
}
