<?php

declare(strict_types=1);

namespace AwardWallet\MainBundle\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;
use Symfony\Component\DependencyInjection\ContainerAwareTrait;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20230328070815 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // check if Point column exists in table Usr
        $pointExists = $this->connection->executeQuery("select * from INFORMATION_SCHEMA.COLUMNS where TABLE_NAME = 'Usr' and COLUMN_NAME = 'Point'")->fetchOne();

        if (!$pointExists) {
            $this->addSql("
                alter table Usr
                    add column `Lat` double default null comment 'Широта (для геолокации по IP)',
                    add column `Lng` double default null comment 'Долгота (для геолокации по IP)',
                    add column `IsPointSet` bool default false not null comment '*false* - если координаты не установлены и значение в колонке Point (NOT NULL) выставлено на Северный полюс и ему нельзя верить. *true* - значению в колонке Point можно верить'
            ");
            /*
              Here we use Point type with structure as Point(Lng, Lat) — first Longitude (for X), then Latitude (Y).
              ST_SRID(Point(Lng, Lat), 4326) — sets ST_SRID (Spatial Reference System Identifier) to 4326, which is WGS84, used in GPS. https://epsg.io/4326
             */
            $this->addSql("alter table Usr add column `Point` point generated always as (ST_SRID(IFNULL(Point(Lng, Lat), Point(0, 90)), 4326)) stored srid 4326 not null comment 'Точка на карте (для геолокации по IP)'");
        }

        $pointSetIndexExists = $this->connection->executeQuery("select * from INFORMATION_SCHEMA.STATISTICS where  TABLE_NAME = 'Usr' and INDEX_NAME = 'Usr_IsPointSet'")->fetchOne();

        if (!$pointSetIndexExists) {
            $this->addSql('alter table Usr add index `Usr_IsPointSet` (`IsPointSet`)');
        }
    }

    public function down(Schema $schema): void
    {
        $this->addSql('alter table Usr drop index `Usr_IsPointSet`');
        $this->addSql('alter table Usr drop index `Usr_Point`');
        $this->addSql('alter table Usr drop column `Point`');
        $this->addSql('
            alter table Usr 
                drop column `IsPointSet`, 
                drop column `Lng`, 
                drop column `Lat`
        ');
    }
}
