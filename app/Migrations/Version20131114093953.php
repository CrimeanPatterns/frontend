<?php

namespace AwardWallet\MainBundle\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
class Version20131114093953 extends AbstractMigration
{
    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql("drop table OfferBan");
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql("create table OfferBan
                      (OfferBanID int unsigned not null auto_increment,
                       UserID int not null,
                       Reason varchar(64),
                       PRIMARY KEY (OfferBanID))
                      ENGINE=InnoDB DEFAULT CHARSET=utf8");
        $this->addSql("ALTER TABLE `OfferBan` ADD UNIQUE KEY `UserID` (`UserID`)");
    }
}
