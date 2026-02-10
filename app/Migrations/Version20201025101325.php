<?php

declare(strict_types=1);

namespace AwardWallet\MainBundle\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20201025101325 extends AbstractMigration
{
    public function up(Schema $schema): void
    {
        $this->addSql("alter table AirCode 
            ADD `Popularity` int(11) NULL DEFAULT 0 comment 'Популярность аэропорта. Количество вылетов из этого аэропорта в будущие полгода по таблице TripSegment';
        ");

        $schema->getTable('AirCode')->getColumn('AirCodeID')->setComment('Идентификатор');
        $schema->getTable('AirCode')->getColumn('AirCode')->setComment('IATA-код аэропорта');
        $schema->getTable('AirCode')->getColumn('AirName')->setComment('Название аэропорта');
        $schema->getTable('AirCode')->getColumn('CityCode')->setComment('Код города, к которому относится аэропорт');
        $schema->getTable('AirCode')->getColumn('CityName')->setComment('Название города, к которому относится аэропорт');
        $schema->getTable('AirCode')->getColumn('CountryCode')->setComment('Код страны, в которой расположен аэропорт');
        $schema->getTable('AirCode')->getColumn('CountryName')->setComment('Название страны, в которой расположен аэропорт');
        $schema->getTable('AirCode')->getColumn('State')->setComment('Код штата, в которой расположен аэропорт');
        $schema->getTable('AirCode')->getColumn('StateName')->setComment('Название штата, в которой расположен аэропорт');
        $schema->getTable('AirCode')->getColumn('Lat')->setComment('Широта аэропорта в десятичных градусах');
        $schema->getTable('AirCode')->getColumn('Lng')->setComment('Долгота аэропорта в десятичных градусах');
        $schema->getTable('AirCode')->getColumn('TimeZone')->setComment('Смещение от UTC (в секундах) в аэропорту в момент, когда сделан запрос');
        $schema->getTable('AirCode')->getColumn('TimeZoneID')->setComment('Идентификатор из TimeZone');
        $schema->getTable('AirCode')->getColumn('LastUpdateDate')->setComment('Дата последнего обновления');
        $schema->getTable('AirCode')->getColumn('IcaoCode')->setComment('ICAO-код аэропорта');
        $schema->getTable('AirCode')->getColumn('Fs')->setComment('Уникальный Cirium(FlightStats) код аэропорта(не изменяется)');
        $schema->getTable('AirCode')->getColumn('Faa')->setComment('FAA-код');
        $schema->getTable('AirCode')->getColumn('Classification')->setComment('Класссификация Cirium(FlightStats) аэропортов, от 1 до 5.
1 = Top 100 Airports based on Number of Departures.
2 = Next 200 Airports based on Number of Departures.
3 = Next 400 Airports based on Number of Departures.
4 = Has Departure in FlightHistory.
5 = Does not have Departure in FlightHistory.');
    }

    public function down(Schema $schema): void
    {
        $this->addSql("alter table AirCode drop `Popularity`");
    }
}
