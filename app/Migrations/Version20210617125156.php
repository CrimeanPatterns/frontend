<?php

declare(strict_types=1);

namespace AwardWallet\MainBundle\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20210617125156 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        $this->addSql("create table TpoHotel(
            id varchar(20) not null,
            name varchar(512) not null,
            latitude float not null,
            longitude float not null,
            update_date datetime not null comment 'Когда мы обновили эту запись',
            primary key (id),
            index (latitude)  
        ) engine InnoDB comment 'список отелей с https://support.travelpayouts.com/hc/en-us/articles/115000343268-Hotels-data-API#35, для массового поиска отелей в MultiHotelSource'");
    }

    public function down(Schema $schema): void
    {
        $this->addSql("drop table TpoHotel");
    }
}
