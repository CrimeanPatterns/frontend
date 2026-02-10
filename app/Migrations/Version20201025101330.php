<?php

declare(strict_types=1);

namespace AwardWallet\MainBundle\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20201025101330 extends AbstractMigration
{
    public function up(Schema $schema): void
    {
        $this->addSql("
            CREATE TABLE `Lounge` (
                LoungeID INT(11) NOT NULL AUTO_INCREMENT COMMENT 'Идентификатор',
                Name VARCHAR(255) NOT NULL COMMENT 'Название',
                AirportCode VARCHAR(3) NOT NULL COMMENT 'IATA-код аэропорта, к которому относится зал',
                Terminal VARCHAR(100) DEFAULT NULL COMMENT 'Терминал аэропорта, в котором располагается зал',
                Gate VARCHAR(100) DEFAULT NULL COMMENT 'Gate аэропорта',
                AirlineID INT(11) DEFAULT NULL COMMENT 'Идентификатор авиалинии, которой принадлежит зал',
                AllianceID INT(11) DEFAULT NULL COMMENT 'Идентификатор альянса авиалиний',
                OpeningHours VARCHAR(4096) DEFAULT NULL COMMENT 'Часы работы',
                isAvailable TINYINT DEFAULT 1 COMMENT 'Работает или нет. 0 - Нет / 1 - Да',
                Location VARCHAR(4096) DEFAULT NULL COMMENT 'Расположение зала внутри аэропорта',
                AdditionalInfo TEXT DEFAULT NULL COMMENT 'Дополнительная информация',
                Amenities VARCHAR(4096) DEFAULT NULL COMMENT 'Удобства',
                Rules TEXT DEFAULT NULL COMMENT 'Условия входа в зал',
                SourceCode VARCHAR(50) NOT NULL COMMENT 'Код источника данных',
                SourceID VARCHAR(255) DEFAULT NULL COMMENT 'Уникальный идентификатор зала у источника данных',
                URL VARCHAR(255) DEFAULT NULL COMMENT 'URL, с которого взяты данные',
                PageBody TEXT NOT NULL COMMENT 'Тело страницы с данными о зале',
                CreateDate DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP COMMENT 'Дата создания записи',
                UpdateDate DATETIME DEFAULT NULL COMMENT 'Дата последнего обновления',
                PriorityPassAccess TINYINT DEFAULT 0 COMMENT 'Есть ли доступ по PriorityPass. 0 - Нет / 1 - Да',
                AmexPlatinumAccess TINYINT DEFAULT 0 COMMENT 'Есть ли доступ по AmexPlatinum. 0 - Нет / 1 - Да',
                Status TINYINT DEFAULT 1 COMMENT 'Статус. 0 - неактивная запись / 1 - Актуальная запись',
                PRIMARY KEY (`LoungeID`),
                INDEX `Lounge_AirportCode_idx` (`AirportCode` ASC),
                CONSTRAINT `AirlineID_fk` FOREIGN KEY (`AirlineID`) REFERENCES `Airline` (`AirlineID`) ON DELETE CASCADE ON UPDATE CASCADE,
                CONSTRAINT `AllianceID_fk` FOREIGN KEY (`AllianceID`) REFERENCES `Alliance` (`AllianceID`)
            )
            engine=InnoDB comment 'Залы ожидания аэпортов';
        ");
    }

    public function down(Schema $schema): void
    {
        $this->addSql("drop table `Lounge`");
    }
}
