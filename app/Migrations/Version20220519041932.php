<?php

declare(strict_types=1);

namespace AwardWallet\MainBundle\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20220519041932 extends AbstractMigration
{
    public function up(Schema $schema): void
    {
        $this->addSql("
            CREATE TABLE AirportTerminal (
                AirportTerminalID INT(11) NOT NULL AUTO_INCREMENT COMMENT 'Идентификатор',
                AirportCode VARCHAR(3) NOT NULL COMMENT 'IATA-код аэропорта',
                Name VARCHAR(100) NOT NULL COMMENT 'Название',
                CreateDate DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP COMMENT 'Дата создания',
                UpdateDate DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP COMMENT 'Дата последнего обновления',
                PRIMARY KEY (AirportTerminalID),
                UNIQUE KEY (AirportCode, Name)
            ) ENGINE=InnoDB COMMENT 'Терминалы аэропортов';
        ");
        $this->addSql("
            CREATE TABLE AirportTerminalAlias (
                AirportTerminalID INT(11) NOT NULL COMMENT 'Ссылка на терминал',
                Alias VARCHAR(100) NOT NULL COMMENT 'Вариация названия терминала',
                CreateDate DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP COMMENT 'Дата создания',
                UpdateDate DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP COMMENT 'Дата последнего обновления',
                PRIMARY KEY (AirportTerminalID, Alias),
                CONSTRAINT AirportTerminalAlias_AirportTerminalID_fk FOREIGN KEY (AirportTerminalID) REFERENCES AirportTerminal (AirportTerminalID) ON DELETE CASCADE ON UPDATE CASCADE
            ) ENGINE=InnoDB COMMENT 'Терминалы аэропортов';
        ");
    }

    public function down(Schema $schema): void
    {
        $this->addSql("
            DROP TABLE AirportTerminalAlias;
            DROP TABLE AirportTerminal;
        ");
    }
}
