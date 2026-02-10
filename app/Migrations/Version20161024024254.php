<?php

namespace AwardWallet\MainBundle\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

class Version20161024024254 extends AbstractMigration
{
    public function up(Schema $schema): void
    {
        $this->addSql("ALTER TABLE `FlightInfo` DROP FOREIGN KEY FlightInfo_ibfk_1");
        $this->addSql("DROP INDEX `ProviderID` ON `FlightInfo`");
        $this->addSql("alter table `FlightInfo` drop `ProviderID`");
        $this->addSql("ALTER TABLE `FlightInfo` ADD UNIQUE (`Airline`, `FlightNumber`, `FlightDate`, `DepCode`, `ArrCode`)");
    }

    public function down(Schema $schema): void
    {
        $this->addSql("DELETE FROM `FlightInfo`");

        $this->addSql("ALTER TABLE `FlightInfo` ADD COLUMN `ProviderID` int NOT NULL");
        $this->addSql("DROP INDEX `Airline` ON `FlightInfo`");
        $this->addSql("ALTER TABLE `FlightInfo` ADD UNIQUE (`ProviderID`, `FlightNumber`, `FlightDate`, `DepCode`, `ArrCode`)");
        $this->addSql("ALTER TABLE `FlightInfo` ADD FOREIGN KEY(`ProviderID`) REFERENCES Provider(`ProviderID`) on delete cascade");
    }
}
