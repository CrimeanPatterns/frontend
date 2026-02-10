<?php

declare(strict_types=1);

namespace AwardWallet\MainBundle\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20231207082701 extends AbstractMigration
{
    public function up(Schema $schema): void
    {
        $this->addSql("
            ALTER TABLE RAFlightSearchRoute
                ADD COLUMN FlightDurationSeconds INT(11) NULL AFTER FlightDuration,
                ADD COLUMN LayoverDurationSeconds INT(11) NULL AFTER LayoverDuration
        ");
        $this->addSql("
            ALTER TABLE RAFlightSearchRouteSegment
                ADD COLUMN FlightDurationSeconds INT(11) NULL AFTER FlightDuration,
                ADD COLUMN LayoverDurationSeconds INT(11) NULL AFTER LayoverDuration
        ");

        $this->addSql('
            UPDATE RAFlightSearchRoute
            SET FlightDurationSeconds = IF(FlightDuration IS NOT NULL AND FlightDuration REGEXP \'^[0-9]{1,2}:[0-9]{1,2}$\', TIME_TO_SEC(FlightDuration), NULL),
                LayoverDurationSeconds = IF(LayoverDuration IS NOT NULL AND LayoverDuration REGEXP \'^[0-9]{1,2}:[0-9]{1,2}$\', TIME_TO_SEC(LayoverDuration), NULL)
        ');
        $this->addSql('
            UPDATE RAFlightSearchRouteSegment
            SET FlightDurationSeconds = IF(FlightDuration IS NOT NULL AND FlightDuration REGEXP \'^[0-9]{1,2}:[0-9]{1,2}$\', TIME_TO_SEC(FlightDuration), NULL),
                LayoverDurationSeconds = IF(LayoverDuration IS NOT NULL AND LayoverDuration REGEXP \'^[0-9]{1,2}:[0-9]{1,2}$\', TIME_TO_SEC(LayoverDuration), NULL)
        ');
    }

    public function down(Schema $schema): void
    {
        $this->addSql("
            ALTER RAFlightSearchRouteSegment
                DROP COLUMN FlightDurationSeconds,
                DROP COLUMN LayoverDurationSeconds
        ");
        $this->addSql("
            ALTER TABLE RAFlightSearchRoute
                DROP COLUMN FlightDurationSeconds,
                DROP COLUMN LayoverDurationSeconds
        ");
    }
}
