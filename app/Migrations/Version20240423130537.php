<?php

declare(strict_types=1);

namespace AwardWallet\MainBundle\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20240423130537 extends AbstractMigration
{
    public function up(Schema $schema): void
    {
        $this->addSql("
            CREATE TABLE RaFlightFullSearchStat (
                DepartureAirportCode VARCHAR(3) NOT NULL COMMENT 'Аэропорт отправления',
                ArrivalAirportCode VARCHAR(3) NOT NULL COMMENT 'Аэропорт прибытия',
                Period INT NOT NULL COMMENT 'Неделя года',
                FlightClass VARCHAR(30) NOT NULL COMMENT 'Класс перелета',
                PassengersCount INT NOT NULL COMMENT 'Количество пассажиров',
                LastSearchDate DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP COMMENT 'Дата последнего поиска',
                LastFullSearchDate DATETIME NULL COMMENT 'Дата последнего полного поиска',
                PRIMARY KEY (DepartureAirportCode, ArrivalAirportCode, Period, FlightClass, PassengersCount)
            )
        ");

        $this->addSql("
            ALTER TABLE RAFlightSearchQuery
            CHANGE COLUMN Parsers Parsers TEXT NULL COMMENT 'Парсеры, которые будут использоваться для поиска',
            ADD COLUMN AutoSelectParsers TINYINT(1) NOT NULL DEFAULT 0 COMMENT 'Автоматически выбирать парсеры' AFTER Parsers,
            ADD COLUMN LastSearchKey VARCHAR(255) NULL COMMENT 'Ключ последнего поиска' AFTER LastSearchDate
        ");
    }

    public function down(Schema $schema): void
    {
        $this->addSql('DROP TABLE RaFlightFullSearchStat');
        $this->addSql("
            ALTER TABLE RAFlightSearchQuery
            DROP COLUMN AutoSelectParsers,
            DROP COLUMN LastSearchKey,
            CHANGE COLUMN Parsers Parsers TEXT NOT NULL COMMENT 'Парсеры, которые будут использоваться для поиска'
        ");
    }
}
