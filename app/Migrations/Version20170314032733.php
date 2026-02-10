<?php

namespace AwardWallet\MainBundle\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
class Version20170314032733 extends AbstractMigration
{
    public function up(Schema $schema): void
    {
        $this->addSql("create table Overlay(
            Kind varchar(5) not null comment 'Тип сущности на которую будет наложен оверлэй, например TS - TripSegment',
            ID varchar(80) not null comment 'Уникальный идентификатор сущности на которую будет наложен overlay, например DL.2215.2016-08-01T22:55 для трипсегмента',
            Source varchar(20) not null comment 'Уникальный идентификатор источника, откуда пришли эти данные, и который будет конвертировать эти данные в формат парсера, например \'fs.alerts\'',
            Data mediumtext not null comment 'Данные полученные от источника, в удобном процессору формате',
            CreateDate datetime not null,
            UpdateDate datetime not null,
            ExpirationDate datetime not null,
            primary key(Kind, ID, Source),
            key (ExpirationDate)
       ) engine=InnoDB comment='Данные которые получены из дополнительных источников (flightstats) и будут накладываться поверх данных полученных от парсеров'");
    }

    public function down(Schema $schema): void
    {
        $this->addSql("drop table Overlay");
    }
}
