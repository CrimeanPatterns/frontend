<?php

namespace AwardWallet\MainBundle\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

class Version20161013112215 extends AbstractMigration
{
    public function up(Schema $schema): void
    {
        $this->addSql("DELETE FROM `FlightInfo`");

        $this->addSql("ALTER TABLE FlightInfo MODIFY COLUMN UpdateDate DATETIME NULL");

        $this->addSql("ALTER TABLE `FlightInfo` DROP FOREIGN KEY FlightInfo_ibfk_1");
        $this->addSql("DROP INDEX `ProviderID` ON `FlightInfo`");
        $this->addSql("ALTER TABLE `FlightInfo` ADD UNIQUE (`ProviderID`, `FlightNumber`, `FlightDate`, `DepCode`, `ArrCode`)");
        $this->addSql("ALTER TABLE `FlightInfo` ADD FOREIGN KEY(`ProviderID`) REFERENCES Provider(`ProviderID`) on delete cascade");
    }

    public function down(Schema $schema): void
    {
        $this->addSql("DELETE FROM `FlightInfo`");

        $this->addSql("ALTER TABLE `FlightInfo` DROP FOREIGN KEY FlightInfo_ibfk_1");
        $this->addSql("DROP INDEX `ProviderID` ON `FlightInfo`");
        $this->addSql("ALTER TABLE `FlightInfo` ADD UNIQUE (`ProviderID`, `FlightNumber`, `FlightDate`)");
        $this->addSql("ALTER TABLE `FlightInfo` ADD FOREIGN KEY(`ProviderID`) REFERENCES Provider(`ProviderID`) on delete cascade");
    }
}
