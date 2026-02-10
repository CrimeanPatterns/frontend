<?php

namespace AwardWallet\MainBundle\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
class Version20161108050624 extends AbstractMigration
{
    public function up(Schema $schema): void
    {
        $this->addSql("ALTER TABLE `TripSegment` ADD COLUMN `CheckinNotificationDate` DATETIME DEFAULT NULL COMMENT 'дата последней check-in нотификации'");

        $this->addSql("
            UPDATE TripSegment ts
            LEFT JOIN GeoTag gt ON ts.DepGeoTagID = gt.GeoTagID
            LEFT JOIN TimeZone tz ON gt.TimeZoneID = tz.TimeZoneID
            SET CheckinNotificationDate = 
                IF(
                    tz.TimeZoneID IS NULL,
                    DATE_ADD(ts.DepDate, INTERVAL -IFNULL(gt.TimeZone, 0) - 24*3600 SECOND),
                    CONVERT_TZ(DATE_ADD(ts.DepDate, INTERVAL -24 HOUR), tz.Location, 'UTC')
                )
            WHERE CheckinNotified = 1
        ");
    }

    public function down(Schema $schema): void
    {
        $this->addSql('UPDATE `TripSegment` SET CheckinNotified = IF(CheckinNotificationDate IS NOT NULL, 1, 0)');

        $this->addSql('ALTER TABLE `TripSegment` DROP COLUMN `CheckinNotificationDate`');
    }
}
