<?php

declare(strict_types=1);

namespace AwardWallet\MainBundle\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20250313074204 extends AbstractMigration
{
    public function up(Schema $schema): void
    {
        $this->addSql('
            ALTER TABLE RAFlight DROP INDEX idx_RAFlight_Filter_MileCost;
        ');
        $this->addSql('
            ALTER TABLE RAFlight 
                ADD INDEX idx_RAFlight_FromAirport (FromAirport),
                ADD INDEX idx_RAFlight_ToAirport (ToAirport),
                ADD INDEX idx_RAFlight_Filter_MileCost (Provider, StandardItineraryCOS, FromCountry, ToCountry, AwardType, FlightType, SearchDate, MileCost);
        ');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('
            ALTER TABLE RAFlight 
                DROP INDEX idx_RAFlight_Filter_MileCost,
                DROP INDEX idx_RAFlight_ToAirport,
                DROP INDEX idx_RAFlight_FromAirport;
        ');
        $this->addSql('
            ALTER TABLE RAFlight ADD INDEX idx_RAFlight_Filter_MileCost (Provider, SearchDate, FromAirport, ToAirport, StandardItineraryCOS, FlightType, AwardType, MileCost);
        ');
    }
}
