<?php

declare(strict_types=1);

namespace AwardWallet\MainBundle\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20230901095641 extends AbstractMigration
{
    public function up(Schema $schema): void
    {
        $this->addSql("
            CREATE TABLE AirportConcourse
                (
                    AirportConcourseID INT(11) NOT NULL AUTO_INCREMENT COMMENT 'Идентификатор',
                    AirportCode VARCHAR(3) NOT NULL COMMENT 'IATA-код аэропорта',
                    Name VARCHAR(100) NOT NULL COMMENT 'Название',
                    AirportTerminalID INT(11) NULL COMMENT 'Ссылка на терминал',
                    CreateDate DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP NOT NULL COMMENT 'Дата создания',
                    UpdateDate DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP NOT NULL COMMENT 'Дата последнего обновления',
                    PRIMARY KEY (AirportConcourseID),
                    UNIQUE KEY (AirportCode, Name),
                    CONSTRAINT AirportConcourse_AirportTerminalID_fk
                        FOREIGN KEY (AirportTerminalID) REFERENCES AirportTerminal (AirportTerminalID) ON DELETE CASCADE ON UPDATE CASCADE
                )
                ENGINE=InnoDB COMMENT 'Конкорсы аэропорта';
        ");
    }

    public function down(Schema $schema): void
    {
        $this->addSql("
            DROP TABLE AirportConcourse;
        ");
    }
}
