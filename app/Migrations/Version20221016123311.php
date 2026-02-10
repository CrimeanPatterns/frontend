<?php

declare(strict_types=1);

namespace AwardWallet\MainBundle\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20221016123311 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql("
            CREATE TABLE `RAFlight` (
                `RAFlightID` int NOT NULL AUTO_INCREMENT,
                `RequestID` varchar(100) NOT NULL COMMENT 'ID запроса на reward-availability',
                `SearchDate` datetime NOT NULL COMMENT 'время запроса на reward-availability, когда были полученны данные',
                `Provider` varchar(20) NOT NULL COMMENT 'код провайдера, у которого собрали перелет',
                `Airlines` varchar(20) NOT NULL COMMENT 'iata коды авиалиний через зпт, которыми совершается перелет',
                `Cabins` varchar(120) NOT NULL COMMENT 'кэбины через зпт, которыми совершается перелет',
                `FareClasses` varchar(120) NOT NULL COMMENT 'FareClasse через зпт, которыми совершается перелет',
                `AwardType` varchar(55) NOT NULL COMMENT 'AwardType которым совершается перелет',
                `TypeFlight` tinyint NOT NULL DEFAULT '0' COMMENT 'тип перелета: 0 - самый короткий, 1 - все перелеты првайдера, 2 - все перелеты партнеров, 3 - микс авиакомпаний',
                `Route` varchar(255) NOT NULL DEFAULT '' COMMENT 'весь маршрут, все сегменты и остановки',
                `FromAirport` varchar(3) NOT NULL DEFAULT '' COMMENT 'IATA-код аэропорта вылета',
                `FromRegion` varchar(120) NOT NULL DEFAULT '' COMMENT 'регион аэропорта вылета',
                `FromCountry` varchar(80) NOT NULL DEFAULT '' COMMENT 'страна аэропорта вылета',
                `ToAirport` varchar(3) NOT NULL DEFAULT '' COMMENT 'IATA-код аэропорта прилета',
                `ToRegion` varchar(120) NOT NULL DEFAULT '' COMMENT 'регион аэропорта прилета',
                `ToCountry` varchar(80) NOT NULL DEFAULT '' COMMENT 'страна аэропорта прилета',
                `MileCost` int DEFAULT NULL COMMENT 'стоимость перелета в милях',
                `Taxes` float DEFAULT NULL COMMENT 'стоимость перелета в валюте',
                `Currency` varchar(3) DEFAULT NULL COMMENT 'название валюты, в которой таксы собраны',
                `DaysBeforeDeparture` int NOT NULL DEFAULT '0' COMMENT 'кол-во дней полных от даты сбора до датые вылета',
                `DepartureDate` datetime NOT NULL COMMENT 'время вылета',
                `ArrivalDate` datetime NOT NULL COMMENT 'время прилета',
                `TravelTime` int NOT NULL COMMENT 'время перелета в минутах',
                `Stopovers` tinyint NOT NULL DEFAULT '0' COMMENT 'сколько stopovers',
                `Layovers` tinyint NOT NULL DEFAULT '0' COMMENT 'сколько layovers',
                `TotalDistance` float NOT NULL DEFAULT '0' COMMENT 'общая дистанция в милях',
                `LayoverOne` varchar(3) DEFAULT '' COMMENT 'в каком аэропорту layover, если есть',
                `LayoverOneDistance` float NOT NULL DEFAULT '0' COMMENT 'растояние до первого layover в милях',
                `StopoverOne` varchar(3) DEFAULT '' COMMENT 'в каком аэропорту stopover, если есть',
                `StopoverOneDistance` float NOT NULL DEFAULT '0' COMMENT 'растояние до первого Stopover в милях',
                `LayoverTwo` varchar(3) DEFAULT '' COMMENT 'в каком аэропорту еще один layover, если есть',
                `LayoverTwoDistance` float NOT NULL DEFAULT '0' COMMENT 'растояние до второго layover в милях',
                `StopoverTwo` varchar(3) DEFAULT '' COMMENT 'в каком аэропорту stopover, если есть еще один',
                `StopoverTwoDistance` float NOT NULL DEFAULT '0' COMMENT 'растояние до второго Stopover в милях',
                PRIMARY KEY (`RAFlightID`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COMMENT='перелеты Reward Availability'
        ");
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql("DROP TABLE RAFlight");

    }
}
