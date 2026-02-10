<?php

declare(strict_types=1);

namespace AwardWallet\MainBundle\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20210218095856 extends AbstractMigration
{
    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs

        $this->addSql("
            ALTER TABLE AirCode 
                CHANGE TimeZone TimeZone_DELETE INT DEFAULT 0 NOT NULL COMMENT 'Смещение от UTC (в секундах) в аэропорту в момент, когда сделан запрос',
                CHANGE TimeZoneID TimeZoneID_DELETE INT NULL COMMENT 'Идентификатор из TimeZone';
            
            ALTER TABLE GeoTag
                DROP FOREIGN KEY FK_GeoTag_TimeZone,
                CHANGE TimeZone TimeZone_DELETE INT NULL,
                CHANGE TimeZoneID TimeZoneID_DELETE INT NULL;

            ALTER TABLE StationCode CHANGE TimeZoneID TimeZoneID_DELETE INT NULL;
            
            RENAME TABLE TimeZone TO TimeZone_DELETE;             
        ");
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql("
            RENAME TABLE TimeZone_DELETE TO TimeZone;
            
            ALTER TABLE StationCode CHANGE TimeZoneID_DELETE TimeZoneID INT NULL;
            
            ALTER TABLE GeoTag
                CHANGE TimeZone_DELETE TimeZone INT NULL,
                CHANGE TimeZoneID_DELETE TimeZoneID INT NULL,
                ADD CONSTRAINT FK_GeoTag_TimeZone
                    FOREIGN KEY (TimeZoneID) REFERENCES TimeZone (TimeZoneID)
                        ON UPDATE CASCADE ON DELETE CASCADE;

            ALTER TABLE AirCode
                CHANGE TimeZone_DELETE TimeZone INT DEFAULT 0 NOT NULL COMMENT 'Смещение от UTC (в секундах) в аэропорту в момент, когда сделан запрос',
                CHANGE TimeZoneID_DELETE TimeZoneID INT NULL COMMENT 'Идентификатор из TimeZone';
        ");
    }
}
