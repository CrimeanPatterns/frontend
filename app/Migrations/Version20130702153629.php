<?php

namespace AwardWallet\MainBundle\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your need!
 */
class Version20130702153629 extends AbstractMigration
{
    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql("
            create table `OfferLog`
                (`OfferLogID` int unsigned auto_increment,
            `OfferID` int,
            `UserID` int unsigned,
            Action int,
            primary key (`OfferLogID`)
            )
            ENGINE=InnoDB
            ");
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql("
			DROP TABLE `OfferLog`;
		");
    }
}
