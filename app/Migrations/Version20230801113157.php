<?php

declare(strict_types=1);

namespace AwardWallet\MainBundle\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20230801113157 extends AbstractMigration
{
    public function up(Schema $schema): void
    {
        $this->addSql("
            CREATE TABLE RAFlightSearchQuery (
                RAFlightSearchQueryID INT AUTO_INCREMENT NOT NULL,
                UserID INT NOT NULL COMMENT 'Кто создал запрос',
                DepartureAirports JSON NOT NULL COMMENT 'Аэропорты вылета',
                ArrivalAirports JSON NOT NULL COMMENT 'Аэропорты прилета',
                DepDateFrom DATE NOT NULL COMMENT 'Дата вылета от',
                DepDateTo DATE NOT NULL COMMENT 'Дата вылета до',
                FlightClass TINYINT NOT NULL COMMENT 'Класс перелета, в 10-ричной системе',
                Adults TINYINT NOT NULL DEFAULT 1 COMMENT 'Количество взрослых',
                SearchInterval TINYINT NOT NULL DEFAULT 1 COMMENT 'Частота поиска. 1 - once, 2 - daily, 3 - weekly',
                Parsers TEXT NOT NULL COMMENT 'Парсеры, которые будут использоваться для поиска',
                EconomyMilesLimit INT NULL COMMENT 'Лимит миль для эконом-класса',
                PremiumEconomyMilesLimit INT NULL COMMENT 'Лимит миль для премиум-эконом-класса',
                BusinessMilesLimit INT NULL COMMENT 'Лимит миль для бизнес-класса',
                FirstMilesLimit INT NULL COMMENT 'Лимит миль для первого класса',
                MileCostLimit DECIMAL(10,2) NULL COMMENT 'Лимит стоимости мили',
                SearchCount INT NOT NULL DEFAULT 0 COMMENT 'Количество поисков',
                CreateDate DATETIME DEFAULT CURRENT_TIMESTAMP NOT NULL COMMENT 'Дата создания запроса',
                UpdateDate DATETIME NULL COMMENT 'Дата обновления запроса',
                LastSearchDate DATETIME NULL COMMENT 'Дата последнего поиска',
                PRIMARY KEY(RAFlightSearchQueryID),
                CONSTRAINT RAFlightSearchQuery_UserID_fk FOREIGN KEY (UserID) REFERENCES Usr (UserID) ON DELETE CASCADE ON UPDATE CASCADE,
                KEY RAFlightSearchQuery_DepDateFrom (DepDateFrom),
                KEY RAFlightSearchQuery_CreateDate (CreateDate),
                KEY RAFlightSearchQuery_LastSearchDate (LastSearchDate)
            ) ENGINE=InnoDB COMMENT 'Поисковые запросы к RAFlight'
        ");

        $this->addSql("
            CREATE TABLE RAFlightSearchResponse (
                RAFlightSearchResponseID INT AUTO_INCREMENT NOT NULL,
                RAFlightSearchQueryID INT NOT NULL COMMENT 'ID запроса',
                ApiRequestID VARCHAR(100) NOT NULL COMMENT 'ID запроса к API',
                RequestDate DATETIME NOT NULL COMMENT 'Дата запроса',
                Parser VARCHAR(250) NOT NULL COMMENT 'Парсер, который использовался для поиска',
                PRIMARY KEY(RAFlightSearchResponseID),
                CONSTRAINT RAFlightSearchResponse_RAFlightSearchQueryID_fk FOREIGN KEY (RAFlightSearchQueryID) REFERENCES RAFlightSearchQuery (RAFlightSearchQueryID) ON DELETE CASCADE ON UPDATE CASCADE
            ) ENGINE=InnoDB COMMENT 'Поисковые ответы от RAFlight'
        ");

        $this->addSql("
            CREATE TABLE RAFlightSearchRoute (
                RAFlightSearchRouteID INT AUTO_INCREMENT NOT NULL,
                RAFlightSearchResponseID INT NOT NULL COMMENT 'ID ответа',
                FlightDuration VARCHAR(100) NULL COMMENT 'Длительность перелета',
                LayoverDuration VARCHAR(100) NULL COMMENT 'Длительность пересадок',
                Stops TINYINT NULL COMMENT 'Количество пересадок',
                Tickets INT NULL COMMENT 'Количество билетов',
                AwardTypes VARCHAR(250) NULL,
                MileCostProgram VARCHAR(250) NULL COMMENT 'Программа лояльности, по которой считается стоимость в милях',
                MileCost INT NULL COMMENT 'Стоимость в милях',
                Currency VARCHAR(3) NULL COMMENT 'Валюта',
                ConversionRate DECIMAL(10,2) NULL COMMENT 'Курс валюты',
                Taxes DECIMAL(10,2) NULL COMMENT 'Налоги',
                Fees DECIMAL(10,2) NULL COMMENT 'Сборы',
                TotalDistance INT NOT NULL COMMENT 'Общее расстояние',
                PRIMARY KEY(RAFlightSearchRouteID),
                CONSTRAINT RAFlightSearchRoute_RAFlightSearchResponseID_fk FOREIGN KEY (RAFlightSearchResponseID) REFERENCES RAFlightSearchResponse (RAFlightSearchResponseID) ON DELETE CASCADE ON UPDATE CASCADE,
                KEY RAFlightSearchRoute_MileCost (MileCost)
            ) ENGINE=InnoDB COMMENT 'Найденные RAFlight перелеты'
        ");

        $this->addSql("
            CREATE TABLE RAFlightSearchRouteSegment (
                RAFlightSearchRouteSegmentID INT AUTO_INCREMENT NOT NULL,
                RAFlightSearchRouteID INT NOT NULL COMMENT 'ID перелета',
                DepDate DATETIME NOT NULL COMMENT 'Дата вылета',
                DepCode VARCHAR(3) NOT NULL COMMENT 'Код аэропорта вылета',
                DepTerminal VARCHAR(50) NULL COMMENT 'Терминал вылета',
                ArrDate DATETIME NOT NULL COMMENT 'Дата прилета',
                ArrCode VARCHAR(3) NOT NULL COMMENT 'Код аэропорта прилета',
                ArrTerminal VARCHAR(50) NULL COMMENT 'Терминал прилета',
                Meal VARCHAR(250) NULL COMMENT 'Питание',
                Service VARCHAR(250) NULL COMMENT 'Класс сервиса',
                FareClass VARCHAR(250) NULL COMMENT 'Класс перелета',
                FlightNumbers JSON NULL COMMENT 'Номера рейсов',
                AirlineCode VARCHAR(3) NULL COMMENT 'Код авиакомпании',
                Aircraft VARCHAR(250) NULL COMMENT 'Тип самолета',
                FlightDuration VARCHAR(100) NULL COMMENT 'Длительность перелета',
                LayoverDuration VARCHAR(100) NULL COMMENT 'Длительность пересадок',
                PRIMARY KEY(RAFlightSearchRouteSegmentID),
                CONSTRAINT RAFlightSearchRouteSegment_RAFlightSearchRouteID_fk FOREIGN KEY (RAFlightSearchRouteID) REFERENCES RAFlightSearchRoute (RAFlightSearchRouteID) ON DELETE CASCADE ON UPDATE CASCADE
            ) ENGINE=InnoDB COMMENT 'Сегменты найденных RAFlight перелетов'
        ");
    }

    public function down(Schema $schema): void
    {
        $this->addSql('DROP TABLE RAFlightSearchRouteSegment');
        $this->addSql('DROP TABLE RAFlightSearchRoute');
        $this->addSql('DROP TABLE RAFlightSearchResponse');
        $this->addSql('DROP TABLE RAFlightSearchQuery');
    }
}
