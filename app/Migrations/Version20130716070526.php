<?php

namespace AwardWallet\MainBundle\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your need!
 */
class Version20130716070526 extends AbstractMigration
{
    public function up(Schema $schema): void
    {
        $this->addSql("ALTER TABLE `BookingRequest` CHANGE `ContactName` `ContactName` VARCHAR(100)  NULL  DEFAULT NULL;");
    }

    public function down(Schema $schema): void
    {
        $this->addSql("ALTER TABLE `BookingRequest` CHANGE `ContactName` `ContactName` VARCHAR(100)  NOT NULL  DEFAULT '';");
    }
}
