<?php

declare(strict_types=1);

namespace AwardWallet\MainBundle\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20220818060409 extends AbstractMigration
{
    public function up(Schema $schema): void
    {
        $this->addSql("DROP TABLE IF EXISTS FlightSegment;");
        $this->addSql("DROP TABLE IF EXISTS Flights;");
        $this->addSql("
            ALTER TABLE TripSegment
                DROP CheckinNotified,
                ADD COLUMN FlightDepartureNotificationDate DATETIME DEFAULT NULL COMMENT 'Дата FlightDeparture нотификации' AFTER CheckinNotificationDate,
                ADD COLUMN FlightBoardingNotificationDate DATETIME DEFAULT NULL COMMENT 'Дата FlightBoarding нотификации' AFTER FlightDepartureNotificationDate,
                ADD COLUMN PreCheckinNotificationDate DATETIME DEFAULT NULL COMMENT 'Дата PreCheckin нотификации' AFTER FlightInfoID;
        ");
    }

    public function down(Schema $schema): void
    {
        $this->addSql("
            ALTER TABLE TripSegment
                ADD CheckinNotified INT DEFAULT 0 NOT NULL COMMENT 'Рассылка произведена' AFTER ArrGeoTagID,
                DROP FlightBoardingNotificationDate,
                DROP FlightDepartureNotificationDate,
                DROP PreCheckinNotificationDate
            ;
        ");
    }
}
