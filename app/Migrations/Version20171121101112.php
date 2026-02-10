<?php

namespace AwardWallet\MainBundle\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

class Version20171121101112 extends AbstractMigration
{
    // refs #15662

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE `Trip` ADD `LastParseDate` DATETIME NULL DEFAULT NULL AFTER `ShareCode`;');
        $this->addSql('ALTER TABLE `Rental` ADD `LastParseDate` DATETIME NULL DEFAULT NULL AFTER `Type`');
        $this->addSql('ALTER TABLE `Reservation` ADD `LastParseDate` DATETIME NULL DEFAULT NULL AFTER `ChangeDate`');
        $this->addSql('ALTER TABLE `Restaurant` ADD `LastParseDate` DATETIME NULL DEFAULT NULL AFTER `ChangeDate`');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE `Trip` DROP `LastParseDate`');
        $this->addSql('ALTER TABLE `Rental` DROP `LastParseDate`');
        $this->addSql('ALTER TABLE `Reservation` DROP `LastParseDate`');
        $this->addSql('ALTER TABLE `Restaurant` DROP `LastParseDate`');
    }
}
