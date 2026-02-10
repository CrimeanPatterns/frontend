<?php declare(strict_types=1);

namespace AwardWallet\MainBundle\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20201026114606 extends AbstractMigration
{
    public function up(Schema $schema) : void
    {
        $this->addSql("
            ALTER TABLE AirCode 
            DROP COLUMN TimeZone_DELETE, 
            DROP COLUMN TimeZoneID_DELETE
        ");

        $this->addSql("
            ALTER TABLE GeoTag 
            DROP COLUMN TimeZone_DELETE, 
            DROP COLUMN TimeZoneID_DELETE
        ");

        $this->addSql("
            ALTER TABLE StationCode
            DROP COLUMN TimeZoneID_DELETE
        ");

        $this->addSql("DROP TABLE TimeZone_DELETE");
    }

    public function down(Schema $schema) : void
    {
        $this->addSql("
            CREATE TABLE IF NOT EXISTS TimeZone_DELETE
            (
                TimeZoneID INT AUTO_INCREMENT PRIMARY KEY,
                Offset INT NULL,
                Name VARCHAR(120) NOT NULL,
                Location VARCHAR(64) NULL,
                IsDefault TINYINT(1) DEFAULT 0 NULL,
                OffsetLastUpdate DATETIME NULL,
                Visible TINYINT UNSIGNED DEFAULT 1 NOT NULL
            );
            
            CREATE INDEX Location ON TimeZone_DELETE (Location);
        ");

        $this->addSql("
            ALTER TABLE AirCode 
            ADD TimeZone_DELETE INT DEFAULT 0 NOT NULL AFTER Lng,
            ADD TimeZoneID_DELETE INT NULL AFTER TimeZone_DELETE,
            ADD INDEX TimeZoneID (TimeZoneID_DELETE)
        ");

        $this->addSql("
            ALTER TABLE GeoTag 
            ADD TimeZone_DELETE INT NULL AFTER FoundAddress,
            ADD TimeZoneID_DELETE INT NULL AFTER TimeZone_DELETE,
            ADD INDEX TimeZoneID (TimeZoneID_DELETE)
        ");

        $this->addSql("
            ALTER TABLE StationCode 
            ADD TimeZoneID_DELETE INT NULL AFTER TimeZoneLocation,
            ADD INDEX TimeZoneID (TimeZoneID_DELETE)
        ");
    }
}
