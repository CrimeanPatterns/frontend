<?php

declare(strict_types=1);

namespace AwardWallet\MainBundle\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20240403100257 extends AbstractMigration
{
    public function up(Schema $schema): void
    {
        $this->addSql("
            CREATE TABLE RAHotelSearchQuery (
                RAHotelSearchQueryID INT AUTO_INCREMENT NOT NULL,
                UserID INT NOT NULL COMMENT 'Кто создал запрос',
                Destination VARCHAR(250) NOT NULL COMMENT 'Место назначения',
                CheckInDate DATE NOT NULL COMMENT 'Дата заезда',
                CheckOutDate DATE NOT NULL COMMENT 'Дата выезда',
                NumberOfRooms TINYINT NOT NULL DEFAULT 1 COMMENT 'Количество комнат',
                NumberOfAdults TINYINT NOT NULL DEFAULT 1 COMMENT 'Количество взрослых',
                NumberOfKids TINYINT NOT NULL DEFAULT 0 COMMENT 'Количество детей',
                DownloadPreview TINYINT NOT NULL DEFAULT 0 COMMENT 'Скачать превью',
                Parsers TEXT NOT NULL COMMENT 'Парсеры, которые будут использоваться для поиска',
                CreateDate DATETIME DEFAULT CURRENT_TIMESTAMP NOT NULL COMMENT 'Дата создания запроса',
                State JSON NULL COMMENT 'Ошибки, состояние, данные для дебага',
                PRIMARY KEY(RAHotelSearchQueryID),
                CONSTRAINT RAHotelSearchQuery_UserID_fk FOREIGN KEY (UserID) REFERENCES Usr (UserID) ON DELETE CASCADE ON UPDATE CASCADE
            ) ENGINE=InnoDB COMMENT 'Поисковые запросы к RAHotel';
        ");
        $this->addSql("
            CREATE TABLE RAHotelSearchResult (
                RAHotelSearchResultID INT AUTO_INCREMENT NOT NULL,
                RAHotelSearchQueryID INT NOT NULL COMMENT 'Ссылка на запрос',
                ProviderID INT NOT NULL COMMENT 'ID провайдера',
                HotelName VARCHAR(250) NOT NULL COMMENT 'Название отеля',
                CheckInDate DATE NOT NULL COMMENT 'Дата заезда',
                CheckOutDate DATE NOT NULL COMMENT 'Дата выезда',
                HotelDescription VARCHAR(250) NULL COMMENT 'Описание отеля',
                RoomType VARCHAR(250) NULL COMMENT 'Тип номера',
                NumberOfNights TINYINT UNSIGNED NOT NULL COMMENT 'Количество ночей',
                PointsPerNight MEDIUMINT UNSIGNED NOT NULL COMMENT 'Количество баллов за ночь',
                CashPerNight DECIMAL(10, 2) NULL COMMENT 'Альтернативная стоимость в долларах',
                OriginalCurrency VARCHAR(16) NULL COMMENT 'Валюта оригинальной стоимости, null - USD',
                Distance DECIMAL(10, 2) NULL COMMENT 'Расстояние от адреса в милях',
                Rating DECIMAL(5, 3) NULL COMMENT 'Рейтинг отеля',
                NumberOfReviews INT UNSIGNED NULL COMMENT 'Количество отзывов',
                Phone VARCHAR(64) NULL COMMENT 'Телефон отеля',
                Address VARCHAR(255) NOT NULL COMMENT 'Адрес отеля',
                AddressLine VARCHAR(255) NULL COMMENT 'Дополнительная строка адреса',
                City VARCHAR(64) NULL COMMENT 'Город',
                State VARCHAR(64) NULL COMMENT 'Штат',
                Country VARCHAR(64) NULL COMMENT 'Страна',
                PostalCode VARCHAR(32) NULL COMMENT 'Почтовый индекс',
                Lat DECIMAL(10, 8) NULL COMMENT 'Широта',
                Lng DECIMAL(11, 8) NULL COMMENT 'Долгота',
                Parser VARCHAR(250) NOT NULL COMMENT 'Парсер, который нашел этот результат',
                ApiRequestID VARCHAR(250) NOT NULL COMMENT 'ID запроса к API',
                CreateDate DATETIME DEFAULT CURRENT_TIMESTAMP NOT NULL COMMENT 'Дата создания результата',
                PRIMARY KEY(RAHotelSearchResultID),
                CONSTRAINT RAHotelSearchResult_RAHotelSearchQueryID_fk FOREIGN KEY (RAHotelSearchQueryID) REFERENCES RAHotelSearchQuery (RAHotelSearchQueryID) ON DELETE CASCADE ON UPDATE CASCADE,
                CONSTRAINT RAHotelSearchResult_ProviderID_fk FOREIGN KEY (ProviderID) REFERENCES Provider (ProviderID) ON DELETE CASCADE ON UPDATE CASCADE
            ) ENGINE=InnoDB COMMENT 'Поисковые результаты от RAHotel';
        ");
        $this->addSql('DROP TABLE IF EXISTS HotelApiResult;');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('
            CREATE TABLE `HotelApiResult` (
              `HotelApiDataID` int NOT NULL AUTO_INCREMENT,
              `ProviderID` int NOT NULL,
              `HotelName` varchar(255) NOT NULL,
              `CheckInDate` datetime NOT NULL,
              `CheckOutDate` datetime DEFAULT NULL,
              `HotelDescription` varchar(255) DEFAULT NULL,
              `NumberOfNights` tinyint unsigned NOT NULL,
              `PointsPerNight` mediumint unsigned DEFAULT NULL,
              `CashPerNight` decimal(10,2) DEFAULT NULL,
              `OriginalCurrency` char(16) DEFAULT NULL,
              `Distance` decimal(10,2) DEFAULT NULL,
              `Rating` decimal(5,3) DEFAULT NULL,
              `NumberOfReviews` mediumint unsigned DEFAULT NULL,
              `Phone` varchar(64) DEFAULT NULL,
              `Address` varchar(255) NOT NULL,
              `AddressLine` varchar(255) DEFAULT NULL,
              `City` varchar(64) DEFAULT NULL,
              `State` varchar(64) DEFAULT NULL,
              `Country` varchar(64) DEFAULT NULL,
              `PostalCode` varchar(32) DEFAULT NULL,
              `Lat` decimal(8,6) DEFAULT NULL,
              `Lng` decimal(8,6) DEFAULT NULL,
              PRIMARY KEY (`HotelApiDataID`),
              UNIQUE KEY `ProviderID` (`ProviderID`,`HotelName`,`CheckInDate`,`CheckOutDate`,`NumberOfNights`,`Address`),
              CONSTRAINT `hotelApiResult_providerId` FOREIGN KEY (`ProviderID`) REFERENCES `Provider` (`ProviderID`) ON DELETE CASCADE ON UPDATE CASCADE
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3
        ');
        $this->addSql('DROP TABLE IF EXISTS RAHotelSearchResult;');
        $this->addSql('DROP TABLE IF EXISTS RAHotelSearchQuery;');
    }
}
