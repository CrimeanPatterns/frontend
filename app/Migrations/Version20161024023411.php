<?php

namespace AwardWallet\MainBundle\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

class Version20161024023411 extends AbstractMigration
{
    public function up(Schema $schema): void
    {
        $this->addSql("DELETE FROM `FlightInfo`");

        $this->addSql("ALTER TABLE `FlightInfo` ADD COLUMN `Airline` varchar(4) NOT NULL DEFAULT ''");
    }

    public function down(Schema $schema): void
    {
        $this->addSql("alter table `FlightInfo` drop `Airline`");
    }
}
