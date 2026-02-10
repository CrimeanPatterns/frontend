<?php

namespace AwardWallet\MainBundle\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

class Version20161011101501 extends AbstractMigration
{
    public function up(Schema $schema): void
    {
        $this->addSql("ALTER TABLE `FlightInfo` ADD COLUMN `DepCode` varchar(10) NOT NULL DEFAULT ''");
        $this->addSql("ALTER TABLE `FlightInfo` ADD COLUMN `ArrCode` varchar(10) NOT NULL DEFAULT ''");
        $this->addSql("ALTER TABLE `FlightInfo` ADD COLUMN `State` tinyint(2) NOT NULL DEFAULT '0'");
        $this->addSql("ALTER TABLE `FlightInfo` ADD COLUMN `ErrorsCount` int(10) NOT NULL DEFAULT '0'");
        $this->addSql("ALTER TABLE `FlightInfo` ADD COLUMN `ErrorMessage` varchar(100) NOT NULL DEFAULT ''");
    }

    public function down(Schema $schema): void
    {
        $this->addSql("alter table `FlightInfo` drop `DepCode`");
        $this->addSql("alter table `FlightInfo` drop `ArrCode`");
        $this->addSql("alter table `FlightInfo` drop `State`");
        $this->addSql("alter table `FlightInfo` drop `ErrorsCount`");
        $this->addSql("alter table `FlightInfo` drop `ErrorMessage`");
    }
}
