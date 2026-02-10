<?php

declare(strict_types=1);

namespace AwardWallet\MainBundle\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20250506085821 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        $this->addSql("
        create table `RACalendar` (
            `RACalendarID` int not null auto_increment,
            `RequestID` varchar(100) NOT NULL COMMENT 'ID запроса на reward-availability',
            `SearchDate` datetime NOT NULL COMMENT 'время запроса на reward-availability, когда были полученны данные',
            `Provider` varchar(20) NOT NULL COMMENT 'код провайдера, у которого собрали перелет',
            `FromAirport` varchar(3) NOT NULL DEFAULT '' COMMENT 'IATA-код аэропорта вылета',
            `ToAirport` varchar(3) NOT NULL DEFAULT '' COMMENT 'IATA-код аэропорта прилета',
            `StandardItineraryCOS` varchar(20) not null default '' COMMENT 'Кэбин в приведенной виде',
            `BrandedItineraryCOS` varchar(50) not null default '' COMMENT 'Кэбин так как он написан на сайте',
            `DepartureDate` date not null COMMENT 'День вылета',
            `MileCost` int not null COMMENT 'Цена в милях',
            `CashCost` float not null COMMENT 'Цена в валюте',
            `Currency` varchar(3) not null COMMENT 'Валюта',
            primary key (`RACalendarID`),
            unique key `idxRACalendarUniqueKey` (`Provider`, `FromAirport`, `ToAirport`, `StandardItineraryCOS`, `BrandedItineraryCOS`, `DepartureDate`),
            index `idxProvider` (`Provider`),
            index `idxRequestID` (`RequestID`),
            index `idxFromAirport` (`FromAirport`),
            index `idxToAirport` (`ToAirport`),
            index `idxStandardItineraryCOS` (`StandardItineraryCOS`),
            index `idxBrandedItineraryCOS` (`BrandedItineraryCOS`),
            index `idxDepartureDate` (`DepartureDate`)
        ) engine=InnoDB DEFAULT CHARSET=utf8mb3 COMMENT='Reward Availability Flight Calendar'");
    }

    public function down(Schema $schema): void
    {
        $this->addSql('drop table if exists RACalendar');
    }
}
