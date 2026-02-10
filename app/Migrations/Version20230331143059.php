<?php

declare(strict_types=1);

namespace AwardWallet\MainBundle\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20230331143059 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        $this->addSql("
            alter table UserIP
                add column `Lat` double default null comment 'Широта (для геолокации по IP)',
                add column `Lng` double default null comment 'Долгота (для геолокации по IP)',
                add column `IsPointSet` bool default false not null comment '*false* - если координаты не установлены и значения в колонках Lat, Lng, Point (NOT NULL) выставлено на Северный полюс и им нельзя верить. *true* - значениям в колонках Lat, Lng, Point можно верить'
        ");
        $this->addSql("alter table UserIP add column `Point` point generated always as (ST_SRID(IFNULL(Point(Lng, Lat), Point(0, 90)), 4326)) stored srid 4326 not null comment 'Точка на карте (для геолокации по IP)'");
        $this->addSql('alter table UserIP add index `UserIP_IsPointSet` (`IsPointSet`)');
    }

    public function down(Schema $schema): void
    {
        // in reverse order
        $this->addSql('alter table UserIP drop index `UserIP_IsPointSet`');
        $this->addSql("alter table UserIP drop column `Point`");
        $this->addSql("
            alter table UserIP
                drop column `IsPointSet`,
                drop column `Lng`,
                drop column `Lat`
        ");
    }
}
