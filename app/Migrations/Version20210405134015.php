<?php declare(strict_types=1);

namespace AwardWallet\MainBundle\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20210405134015 extends AbstractMigration
{
    public function up(Schema $schema) : void
    {
        $this->addSql("
            RENAME TABLE `Lounge` TO `LoungePage`
        ");

        $this->addSql("
            ALTER TABLE `LoungePage` COMMENT = 'Данные о залах ожидания от источников';
        ");

        $this->addSql("
            ALTER TABLE `LoungePage`
              CHANGE LoungeID LoungePageID INT(11)
        ");

        $this->addSql("
            ALTER TABLE `LoungePage`
              ADD LoungeID INT(11) COMMENT 'Ссылка на лаунж'
        ");

        $this->addSql("
            CREATE TABLE `Lounge` (
                LoungeID INT(11) NOT NULL AUTO_INCREMENT COMMENT 'Идентификатор',
                Name VARCHAR(255) NOT NULL COMMENT 'Название',
                AirportCode VARCHAR(3) NOT NULL COMMENT 'IATA-код аэропорта, к которому относится зал',
                Terminal VARCHAR(100) DEFAULT NULL COMMENT 'Терминал аэропорта, в котором располагается зал',
                Gate VARCHAR(100) DEFAULT NULL COMMENT 'Gate аэропорта',
                Gate2 varchar(100) DEFAULT NULL COMMENT 'Второй Gate аэропорта, если указано больше 1',
                AirlineID INT(11) DEFAULT NULL COMMENT 'Идентификатор авиалинии, которой принадлежит зал',
                AllianceID INT(11) DEFAULT NULL COMMENT 'Идентификатор альянса авиалиний',
                OpeningHours VARCHAR(4096) DEFAULT NULL COMMENT 'Часы работы',
                IsAvailable TINYINT DEFAULT 1 COMMENT 'Работает или нет. 0 - Нет / 1 - Да',
                Location VARCHAR(4096) DEFAULT NULL COMMENT 'Расположение зала внутри аэропорта',
                AdditionalInfo TEXT DEFAULT NULL COMMENT 'Дополнительная информация',
                Amenities VARCHAR(4096) DEFAULT NULL COMMENT 'Удобства',
                Rules TEXT DEFAULT NULL COMMENT 'Условия входа в зал',
                CreateDate DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP COMMENT 'Дата создания записи',
                UpdateDate DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP COMMENT 'Дата последнего обновления',
                PriorityPassAccess TINYINT DEFAULT 0 COMMENT 'Есть ли доступ по PriorityPass. 0 - Нет / 1 - Да',
                AmexPlatinumAccess TINYINT DEFAULT 0 COMMENT 'Есть ли доступ по AmexPlatinum. 0 - Нет / 1 - Да',
                Status TINYINT DEFAULT 1 COMMENT 'Статус. 0 - неактивная запись / 1 - Актуальная запись',
                PRIMARY KEY (`LoungeID`),
                INDEX `Lounge_AirportCode_idx` (`AirportCode` ASC),
                CONSTRAINT `Lounge_AirlineID_fk` FOREIGN KEY (`AirlineID`) REFERENCES `Airline` (`AirlineID`) ON DELETE CASCADE ON UPDATE CASCADE,
                CONSTRAINT `Lounge_AllianceID_fk` FOREIGN KEY (`AllianceID`) REFERENCES `Alliance` (`AllianceID`)
            )
             ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci comment 'Залы ожидания аэропортов';
        ");

    }

    public function down(Schema $schema) : void
    {
        $this->addSql("drop table `Lounge`");

        $this->addSql("
            ALTER TABLE `LoungePage`
              DROP LoungeID
        ");

        $this->addSql("
            ALTER TABLE `LoungePage`
              CHANGE LoungePageID LoungeID INT(11)
        ");

        $this->addSql("
            RENAME TABLE `LoungePage` TO `Lounge`
        ");
    }
}
