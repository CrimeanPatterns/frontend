<?php

declare(strict_types=1);

namespace AwardWallet\MainBundle\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20211124095442 extends AbstractMigration
{
    public function up(Schema $schema): void
    {
        $this->addSql("
            RENAME TABLE LoungePage TO LoungeSource;
            
            ALTER TABLE LoungeSource
                CHANGE LoungePageID LoungeSourceID INT(11) NOT NULL AUTO_INCREMENT,
                DROP FOREIGN KEY AirlineID_fk,
                DROP AirlineID,
                DROP FOREIGN KEY AllianceID_fk,
                DROP AllianceID,
                DROP MergeData,
                DROP LocationChanged,
                DROP IsMergable;
                
            CREATE TABLE LoungeSourceAirline (
                LoungeSourceAirlineID INT(11) NOT NULL AUTO_INCREMENT COMMENT 'Идентификатор' PRIMARY KEY,
                LoungeSourceID INT(11) NOT NULL COMMENT 'Ссылка на лаундж',
                AirlineID INT(11) NOT NULL COMMENT 'Ссылка на авиалинию',
                UNIQUE KEY LoungeSourceID_AirlineID (LoungeSourceID, AirlineID),
                CONSTRAINT LoungeSourceAirline_LoungeSourceID_fk FOREIGN KEY (LoungeSourceID) REFERENCES LoungeSource (LoungeSourceID) ON DELETE CASCADE ON UPDATE CASCADE,
                CONSTRAINT LoungeSourceAirline_AirlineID_fk FOREIGN KEY (AirlineID) REFERENCES Airline (AirlineID) ON DELETE CASCADE ON UPDATE CASCADE
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8;
            
            CREATE TABLE LoungeSourceAlliance (
                LoungeSourceAllianceID INT(11) NOT NULL AUTO_INCREMENT COMMENT 'Идентификатор' PRIMARY KEY,
                LoungeSourceID INT(11) NOT NULL COMMENT 'Ссылка на лаундж',
                AllianceID INT(11) NOT NULL COMMENT 'Ссылка на алианс',
                UNIQUE KEY LoungeSourceID_AllianceID (LoungeSourceID, AllianceID),
                CONSTRAINT LoungeSourceAlliance_LoungeSourceID_fk FOREIGN KEY (LoungeSourceID) REFERENCES LoungeSource (LoungeSourceID) ON DELETE CASCADE ON UPDATE CASCADE,
                CONSTRAINT LoungeSourceAlliance_AllianceID_fk FOREIGN KEY (AllianceID) REFERENCES Alliance (AllianceID) ON DELETE CASCADE ON UPDATE CASCADE
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8;
            
            CREATE TABLE LoungeSourceChange (
                LoungeSourceChangeID INT(11) NOT NULL AUTO_INCREMENT COMMENT 'Идентификатор' PRIMARY KEY,
                LoungeSourceID INT(11) NOT NULL COMMENT 'Ссылка на лаундж',
                Property VARCHAR(40) NOT NULL COMMENT 'Изменяемое свойство',
                OldVal TEXT NULL COMMENT 'Старое значение',
                NewVal TEXT NULL COMMENT 'Новое значение',
                ChangeDate DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP COMMENT 'Дата изменения',
                UNIQUE KEY LoungeSourceID_Property_ChangeDate (LoungeSourceID, Property, ChangeDate),
                CONSTRAINT LoungeSourceChange_LoungeSourceID_fk FOREIGN KEY (LoungeSourceID) REFERENCES LoungeSource (LoungeSourceID) ON DELETE CASCADE ON UPDATE CASCADE
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8;
            
            UPDATE LoungeSource SET LoungeID = NULL;
            DELETE FROM Lounge;
            
            ALTER TABLE Lounge
                DROP FOREIGN KEY Lounge_AirlineID_fk,
                DROP AirlineID,
                DROP FOREIGN KEY Lounge_AllianceID_fk,
                DROP AllianceID,
                DROP Status,
                ADD CheckedBy INT NULL COMMENT 'Запись проверена менеджером' AFTER AmexPlatinumAccess,
                ADD CheckedDate DATETIME NULL COMMENT 'Когда была последняя проверка' AFTER CheckedBy,
                ADD Valid TINYINT NOT NULL DEFAULT 0 COMMENT 'Запись проверена и валидна' AFTER CheckedDate;
                
            CREATE TABLE LoungeAirline (
                LoungeAirlineID INT(11) NOT NULL AUTO_INCREMENT COMMENT 'Идентификатор' PRIMARY KEY,
                LoungeID INT(11) NOT NULL COMMENT 'Ссылка на лаундж',
                AirlineID INT(11) NOT NULL COMMENT 'Ссылка на авиалинию',
                UNIQUE KEY LoungeID_AirlineID (LoungeID, AirlineID),
                CONSTRAINT LoungeAirline_LoungeID_fk FOREIGN KEY (LoungeID) REFERENCES Lounge (LoungeID) ON DELETE CASCADE ON UPDATE CASCADE,
                CONSTRAINT LoungeAirline_AirlineID_fk FOREIGN KEY (AirlineID) REFERENCES Airline (AirlineID) ON DELETE CASCADE ON UPDATE CASCADE
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8;
            
            CREATE TABLE LoungeAlliance (
                LoungeAllianceID INT(11) NOT NULL AUTO_INCREMENT COMMENT 'Идентификатор' PRIMARY KEY,
                LoungeID INT(11) NOT NULL COMMENT 'Ссылка на лаундж',
                AllianceID INT(11) NOT NULL COMMENT 'Ссылка на алианс',
                UNIQUE KEY LoungeID_AllianceID (LoungeID, AllianceID),
                CONSTRAINT LoungeAlliance_LoungeID_fk FOREIGN KEY (LoungeID) REFERENCES Lounge (LoungeID) ON DELETE CASCADE ON UPDATE CASCADE,
                CONSTRAINT LoungeAlliance_AllianceID_fk FOREIGN KEY (AllianceID) REFERENCES Alliance (AllianceID) ON DELETE CASCADE ON UPDATE CASCADE
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8;
        ");
    }

    public function down(Schema $schema): void
    {
        $this->addSql("
            DROP TABLE LoungeAlliance;
            DROP TABLE LoungeAirline;

            ALTER TABLE Lounge
                ADD AirlineID INT(11) DEFAULT NULL COMMENT 'Идентификатор авиалинии, которой принадлежит зал' AFTER Gate2,
                ADD CONSTRAINT Lounge_AirlineID_fk FOREIGN KEY (AirlineID) REFERENCES Airline (AirlineID) ON DELETE CASCADE ON UPDATE CASCADE,
                ADD AllianceID INT(11) DEFAULT NULL COMMENT 'Идентификатор альянса авиалиний' AFTER AirlineID,
                ADD CONSTRAINT Lounge_AllianceID_fk FOREIGN KEY (AllianceID) REFERENCES Alliance (AllianceID),
                ADD Status TINYINT NOT NULL DEFAULT 1 COMMENT 'Статус. 0 - неактивная запись / 1 - Актуальная запись' AFTER AmexPlatinumAccess,
                DROP CheckedBy,
                DROP CheckedDate,
                DROP Valid;

            DROP TABLE LoungeSourceChange;
            DROP TABLE LoungeSourceAlliance;
            DROP TABLE LoungeSourceAirline;

            ALTER TABLE LoungeSource
                ADD AirlineID INT(11) DEFAULT NULL COMMENT 'Идентификатор авиалинии, которой принадлежит зал' AFTER Gate2,
                ADD CONSTRAINT AirlineID_fk FOREIGN KEY (AirlineID) REFERENCES Airline (AirlineID) ON DELETE CASCADE ON UPDATE CASCADE,
                ADD AllianceID INT(11) DEFAULT NULL COMMENT 'Идентификатор альянса авиалиний' AFTER AirlineID,
                ADD CONSTRAINT AllianceID_fk FOREIGN KEY (AllianceID) REFERENCES Alliance (AllianceID),
                ADD MergeData JSON NULL COMMENT 'Информация о том, какие поля были использованы для создания/обновления Lounge' AFTER LoungeID,
                ADD LocationChanged JSON NULL COMMENT 'Информация о том, какие поля были использованы для создания/обновления Lounge' AFTER MergeData,
                ADD IsMergable TINYINT DEFAULT 1 NOT NULL COMMENT 'Можно ли мержить' AFTER LocationChanged,
                CHANGE LoungeSourceID LoungePageID INT(11) NOT NULL AUTO_INCREMENT;
                
            RENAME TABLE LoungeSource TO LoungePage;
        ");
    }
}
