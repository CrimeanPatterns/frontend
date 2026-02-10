<?php

namespace AwardWallet\MainBundle\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

class Version20161209223119 extends AbstractMigration
{
    public function up(Schema $schema): void
    {
        $this->addSql("ALTER TABLE `FlightInfo` ADD COLUMN `DepDate` datetime comment 'Дата и время вылета'");
        $this->addSql("ALTER TABLE `FlightInfo` ADD COLUMN `ArrDate` datetime comment 'Дата и время прилёта'");
        $this->addSql("ALTER TABLE `FlightInfo` ADD COLUMN `ChecksCount` int not null default '0' comment 'Счётчик запросов с проверками на существование перелёта'");
        $this->addSql("ALTER TABLE `FlightInfo` ADD COLUMN `SubscribesCount` int not null default '0' comment 'Счётчик запросов с подписками'");
        $this->addSql("ALTER TABLE `FlightInfo` ADD COLUMN `Schedule` varchar(1000) not null default '' comment 'Расписание запросов'");
        $this->addSql("ALTER TABLE `FlightInfo` ADD COLUMN `FlightState` tinyint(2) not null default '0' comment 'Состояние перелёта'");
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE `FlightInfo` DROP COLUMN `DepDate`');
        $this->addSql('ALTER TABLE `FlightInfo` DROP COLUMN `ArrDate`');
        $this->addSql('ALTER TABLE `FlightInfo` DROP COLUMN `ChecksCount`');
        $this->addSql('ALTER TABLE `FlightInfo` DROP COLUMN `SubscribesCount`');
        $this->addSql('ALTER TABLE `FlightInfo` DROP COLUMN `Schedule`');
        $this->addSql('ALTER TABLE `FlightInfo` DROP COLUMN `FlightState`');
    }
}
