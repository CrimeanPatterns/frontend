<?php

declare(strict_types=1);

namespace AwardWallet\MainBundle\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20230510102615 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql(/** @lang MySQL */ "
                    ALTER TABLE RAFlight
                        MODIFY COLUMN `Taxes` DECIMAL(10, 2) NULL;
        ");
        if (!$schema->getTable('RAFlight')->hasIndex('idxRAFlightFilterKey')) {
            $this->addSql(/** @lang MySQL */ "
                    ALTER TABLE RAFlight
                        ADD UNIQUE KEY idxRAFlightFilterKey (`Provider`,`Airlines`,`Cabins`,`FareClasses`,`Route`,`MileCost`,`DaysBeforeDeparture`,`DepartureDate`,`ArrivalDate`,`IsFastest`,`IsCheapest`,`Passengers`,`ClassOfService`,`Taxes`,`SegmentClassOfService`,`AwardType`);
        ");
        }
        if ($schema->getTable('RAFlight')->hasIndex('idxSegmentCOS')) {
            $this->addSql('ALTER TABLE RAFlight 
                        DROP INDEX `idxSearchDate`,
                        DROP INDEX `idxProvider`,
                        DROP INDEX `idxDepartureDate`,
                        DROP INDEX `idxFromAirport`,
                        DROP INDEX `idxFromRegion`,
                        DROP INDEX `idxFromCountry`,
                        DROP INDEX `idxToAirport`,
                        DROP INDEX `idxToRegion`,
                        DROP INDEX `idxToCountry`,
                        DROP INDEX `idxCabins`,
                        DROP INDEX `idxAirlines`,
                        DROP INDEX `idxAwardType`,
                        DROP INDEX `idxTypeFlight`,
                        DROP INDEX `idxMileCost`,
                        DROP INDEX `idxIsMixedCabin`,
                        DROP INDEX `idxIsFastest`,
                        DROP INDEX `idxCabinType`,
                        DROP INDEX `idxClassOfService`,
                        DROP INDEX `idxIsCheapest`,
                        DROP INDEX `idxPassengers`,
                        DROP INDEX `idxSeatsLeft`,
                        DROP INDEX `idxSeatsLeftOnRoute`,
                        DROP INDEX `idxSegmentCOS`;
                    ');
        }
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs

    }
}
