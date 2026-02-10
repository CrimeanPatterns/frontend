<?php

declare(strict_types=1);

namespace AwardWallet\MainBundle\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20230331094212 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('alter table Usr rename index `Usr_IsPointSet` to `Usr_IsLastLogonPointSet`');
        $this->addSql('alter table Usr rename column `IsPointSet` to `IsLastLogonPointSet`');
        $this->addSql('alter table Usr drop column `Point`');
        $this->addSql('alter table Usr rename column `Lat` to `LastLogonLat`');
        $this->addSql('alter table Usr rename column `Lng` to `LastLogonLng`');
        /*
          Here we use Point type with structure as Point(Lng, Lat) — first Longitude (for X), then Latitude (Y).
          ST_SRID(Point(Lng, Lat), 4326) — sets ST_SRID (Spatial Reference System Identifier) to 4326, which is WGS84, used in GPS. https://epsg.io/4326
         */
        $this->addSql("alter table Usr add column `LastLogonPoint` point generated always as (ST_SRID(IFNULL(Point(LastLogonLng, LastLogonLat), Point(0, 90)), 4326)) stored srid 4326 not null comment 'Точка на карте (для геолокации по IP)'");
        $this->addSql("
            alter table Usr
                add column `ResidentLat` double default null comment 'Широта (для геолокации по IP)',
                add column `ResidentLng` double default null comment 'Долгота (для геолокации по IP)',
                add column `IsResidentPointSet` bool default false not null comment '*false* - если координаты не установлены и значения в колонках ResidentLat, ResidentLng, ResidentPoint (NOT NULL) выставлено на Северный полюс и им нельзя верить. *true* - значениям в колонках ResidentLat, ResidentLng, ResidentPoint можно верить'
        ");
        /*
          Here we use Point type with structure as Point(Lng, Lat) — first Longitude (for X), then Latitude (Y).
          ST_SRID(Point(Lng, Lat), 4326) — sets ST_SRID (Spatial Reference System Identifier) to 4326, which is WGS84, used in GPS. https://epsg.io/4326
         */
        $this->addSql("alter table Usr add column `ResidentPoint` point generated always as (ST_SRID(IFNULL(Point(ResidentLng, ResidentLat), Point(0, 90)), 4326)) stored srid 4326 not null comment 'Точка на карте (для геолокации по IP)'");
        $this->addSql('alter table Usr add index `Usr_IsResidentPointSet` (`IsResidentPointSet`)');
    }

    public function down(Schema $schema): void
    {
        // in reverse order
        if ($this->isIndexExist('Usr', 'Usr_IsResidentPointSet')) {
            $this->addSql('alter table Usr drop index `Usr_IsResidentPointSet`');
        }

        $this->addSql('alter table Usr drop column `ResidentPoint`');
        $this->addSql('
            alter table Usr
                drop column `IsResidentPointSet`,
                drop column `ResidentLng`,
                drop column `ResidentLat`
        ');
        $this->addSql('alter table Usr drop column `LastLogonPoint`');
        $this->addSql('alter table Usr rename column `LastLogonLng` to `Lng`');
        $this->addSql('alter table Usr rename column `LastLogonLat` to `Lat`');
        /*
          Here we use Point type with structure as Point(Lng, Lat) — first Longitude (for X), then Latitude (Y).
          ST_SRID(Point(Lng, Lat), 4326) — sets ST_SRID (Spatial Reference System Identifier) to 4326, which is WGS84, used in GPS. https://epsg.io/4326
         */
        $this->addSql("alter table Usr add column `Point` point generated always as (ST_SRID(IFNULL(Point(Lng, Lat), Point(0, 90)), 4326)) stored srid 4326 not null comment 'Точка на карте (для геолокации по IP)'");
        $this->addSql('alter table Usr rename column `IsLastLogonPointSet` to `IsPointSet`');
        $this->addSql('alter table Usr rename index `Usr_IsLastLogonPointSet` to `Usr_IsPointSet`');
    }

    private function isIndexExist(string $table, string $index): bool
    {
        $indexExists = $this->connection->executeQuery("
            select * 
            from INFORMATION_SCHEMA.STATISTICS 
            where TABLE_NAME = ? and INDEX_NAME = ?",
            [$table, $index]
        )->fetchOne();

        return (bool) $indexExists;
    }
}
