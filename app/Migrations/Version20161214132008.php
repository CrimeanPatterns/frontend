<?php

namespace AwardWallet\MainBundle\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

class Version20161214132008 extends AbstractMigration
{
    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE `FlightInfo` DROP COLUMN `Schedule`');
        $this->addSql("ALTER TABLE `FlightInfo` ADD COLUMN `Schedule` text not null default '' comment 'Расписание запросов'");
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE `FlightInfo` DROP COLUMN `Schedule`');
        $this->addSql("ALTER TABLE `FlightInfo` ADD COLUMN `Schedule` varchar(1000) not null default '' comment 'Расписание запросов'");
    }
}
