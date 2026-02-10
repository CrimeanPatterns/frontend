<?php

declare(strict_types=1);

namespace AwardWallet\MainBundle\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20200130131525 extends AbstractMigration
{
    public function up(Schema $schema): void
    {
        $this->addSql("
            CREATE TABLE `Parking` (
              `ParkingID` int(11) NOT NULL AUTO_INCREMENT,
              `ProviderID` int(11) DEFAULT NULL COMMENT 'Идентификатор провайдера',
              `ProviderName` varchar(80) DEFAULT NULL COMMENT 'Имя провайдера',
              `ParkingCompanyName` varchar(80) DEFAULT NULL COMMENT 'Название компании',
              `Phone` varchar(30) DEFAULT NULL COMMENT 'Номер телефона',
              `Number` varchar(100) DEFAULT NULL COMMENT 'Идентификатор резервации',
              `ConfirmationNumbers` tinytext COMMENT 'Simple Array format (e.g. \"KMGOFA,G7IILB\")',
              `Location` varchar(160) DEFAULT NULL COMMENT 'Название места для парковки',
              `GeoTagID` int(11) DEFAULT NULL COMMENT 'GeoTag места парковки',
              `Spot` varchar(30) DEFAULT NULL COMMENT 'Номер места на парковке',
              `StartDatetime` datetime NOT NULL COMMENT 'Дата и время начала парковки',
              `EndDatetime` datetime NOT NULL COMMENT 'Дата и время окончания парковки',
              `Plate` varchar(20) DEFAULT NULL COMMENT 'Номер машины',
              `CarDescription` varchar(250) DEFAULT NULL COMMENT 'Описание машины',
              `TravelPlanID` int(11) DEFAULT NULL COMMENT 'Идентификатор тревел-плана',
              `ReservationDate` datetime DEFAULT NULL COMMENT 'Дата бронирования',
              `TravelerNames` tinytext COMMENT 'Traveler names as Simple Array (e.g. \"John Doe,Melissa Doe\")',
              `Cancelled` tinyint(2) NOT NULL DEFAULT '0' COMMENT '0 - резервация актуальна, 1 - резервация отменена',
              `ChangeDate` datetime DEFAULT NULL COMMENT 'Дата последнего изменения одного из свойств на сайте провайдера',
              `ParsedStatus` varchar(20) DEFAULT NULL COMMENT 'Спарсенный статус в email или на сайте провайдера',
              `Notes` varchar(4000) DEFAULT NULL COMMENT 'Заметки',
              `MailDate` datetime DEFAULT NULL COMMENT 'Дата отсылки напоминания',
              `Hash` varchar(64) DEFAULT NULL COMMENT 'Хеш, используется для резерваций с отсутствующим Number',
              `UserID` int(11) NOT NULL COMMENT 'Идентификатор пользователя',
              `AccountID` int(11) DEFAULT NULL COMMENT 'Аккаунт',
              `ParsedAccountNumbers` tinytext COMMENT 'Parsed account numbers in Simple Array format (e.g. \"80050130,49304756\")',
              `ConfFields` varchar(250) DEFAULT NULL COMMENT 'Данные подтверждения',
              `CreateDate` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP COMMENT 'Дата создания записи',
              `UpdateDate` datetime DEFAULT NULL COMMENT 'Дата последнего автообновления',
              `LastParseDate` datetime DEFAULT NULL COMMENT 'Дата последнего парсинга',
              `Hidden` smallint(6) NOT NULL DEFAULT '0' COMMENT '0 - показывать, 1 - не показывать(удален)',
              `Parsed` int(11) NOT NULL DEFAULT '0' COMMENT 'установлен в 1 если данные получены в результате парсинга резерваций на сайте провайдера',
              `Moved` int(11) NOT NULL DEFAULT '0' COMMENT 'Флаг для перемещенных вручную сегментов',
              `Copied` tinyint(4) NOT NULL DEFAULT '0' COMMENT 'Флаг скопированных вручную сегментов',
              `Modified` tinyint(4) NOT NULL DEFAULT '0' COMMENT 'Флаг измененных вручную данных сегмента',
              `UserAgentID` int(11) DEFAULT NULL COMMENT 'Идентификатор агента, для которого создана эта резервация',
              `ShareCode` varchar(20) DEFAULT NULL COMMENT 'Случайный набор символов для проверки расшаренного сегмента',
              `Cost` decimal(12,2) DEFAULT NULL COMMENT 'Cost before taxes',
              `CurrencyCode` varchar(5) DEFAULT NULL COMMENT 'Currency code as gathered from the website (e.g. \"USD\")',
              `Discount` decimal(12,2) DEFAULT NULL COMMENT 'Total amount of discounts, if any.',
              `Fees` text COMMENT 'Fees as a serialized array of \\AwardWallet\\MainBundle\\Entity\\Fee objects',
              `Tax` decimal(12,2) DEFAULT NULL COMMENT 'Amount paid in taxes',
              `Total` decimal(12,2) DEFAULT NULL COMMENT 'Total cost of the reservation including all taxes and fees',
              `SpentAwards` varchar(50) DEFAULT NULL COMMENT 'Frequent flier miles, points, or other kinds or bonuses spent on this reservation',
              `EarnedAwards` varchar(50) DEFAULT NULL COMMENT 'Rewards earned for booking the reservation',
              `TravelAgencyID` int(11) DEFAULT NULL COMMENT 'Идентификатор тревел-агенства',
              `TravelAgencyConfirmationNumbers` tinytext COMMENT 'Simple Array format (e.g. \"KMGOFA,G7IILB\")',
              `TravelAgencyPhones` varchar(250) DEFAULT NULL COMMENT 'array, serialized with comma',
              `TravelAgencyParsedAccountNumbers` tinytext COMMENT 'Parsed account numbers in Simple Array format (e.g. \"80050130,49304756\")',
              `TravelAgencyEarnedAwards` varchar(50) DEFAULT NULL COMMENT 'Rewards earned for booking the reservation',
              `PlanIndex` int(11) NOT NULL DEFAULT '0',
              PRIMARY KEY (`ParkingID`)
            ) ENGINE=InnoDB;
        ");
    }

    public function down(Schema $schema): void
    {
        $this->addSql("DROP TABLE `Parking`");
    }
}
