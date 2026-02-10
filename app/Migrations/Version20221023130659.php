<?php

declare(strict_types=1);

namespace AwardWallet\MainBundle\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20221023130659 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('alter table RAFlight add index `idxSearchDate` (`SearchDate`),
                add index `idxProvider` (`Provider`),
                add index `idxDepartureDate` (`DepartureDate`),
                add index `idxFromAirport` (`FromAirport`),
                add index `idxFromRegion` (`FromRegion`),
                add index `idxFromCountry` (`FromCountry`),
                add index `idxToAirport` (`ToAirport`),
                add index `idxToRegion` (`ToRegion`),
                add index `idxToCountry` (`ToCountry`),
                add index `idxCabins` (`Cabins`),
                add index `idxAirlines` (`Airlines`),
                add index `idxAwardType` (`AwardType`),
                add index `idxTypeFlight` (`TypeFlight`),
                add index `idxMileCost` (`MileCost`),
                add index `idxRequestID` (`RequestID`)');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('alter table RAFlight drop index `idxSearchDate`,
                drop index `idxProvider`,
                drop index `idxDepartureDate`,
                drop index `idxFromAirport`,
                drop index `idxFromRegion`,
                drop index `idxFromCountry`,
                drop index `idxToAirport`,
                drop index `idxToRegion`,
                drop index `idxToCountry`,
                drop index `idxCabins`,
                drop index `idxAirlines`,
                drop index `idxAwardType`,
                drop index `idxTypeFlight`,
                drop index `idxMileCost`,
                drop index `idxRequestID`');
    }
}
