<?php

namespace AwardWallet\MainBundle\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
class Version20170823054153 extends AbstractMigration
{
    public function up(Schema $schema): void
    {
        $this->addSql("alter table TripSegment drop foreign key TripInfoID, drop column TripInfoID");
        $this->addSql("drop table TripInfo");
    }

    public function down(Schema $schema): void
    {
        $this->addSql("CREATE TABLE `TripInfo` (
          `TripInfoID` int(11) NOT NULL AUTO_INCREMENT,
          `UserID` int(11) NOT NULL,
          `Mode` tinyint(4) NOT NULL,
          `State` tinyint(4) NOT NULL,
          `StartCode` varchar(20) NOT NULL COMMENT 'Код аэропорта начала путешествия',
          `StartDate` datetime NOT NULL,
          `EndCode` varchar(20) NOT NULL COMMENT 'Код аэропорта окончания путешествия',
          `EndDate` datetime NOT NULL,
          `SyncDate` datetime DEFAULT NULL COMMENT 'Дата последней синхронизации с FlightStats',
          `SyncHash` varchar(200) NOT NULL DEFAULT '' COMMENT 'Хеш последнего запроса',
          PRIMARY KEY (`TripInfoID`),
          KEY `UserID` (`UserID`),
          KEY `StartDate` (`StartDate`),
          CONSTRAINT `TripInfo_ibfk_1` FOREIGN KEY (`UserID`) REFERENCES `Usr` (`UserID`) ON DELETE CASCADE
        ) ENGINE=InnoDB");
        $this->addSql("alter table TripSegment add TripInfoID int");
        $this->addSql("alter table TripSegment add FOREIGN KEY (`TripInfoID`) REFERENCES `TripInfo` (`TripInfoID`) ON DELETE SET NULL");
    }
}
