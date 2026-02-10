<?php

declare(strict_types=1);

namespace AwardWallet\MainBundle\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20180802154527 extends AbstractMigration
{
    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql("
        DROP TABLE IF EXISTS RailCode;
");
        $this->addSql("
CREATE TABLE StationCode (
  StationCodeID INT(11) NOT NULL AUTO_INCREMENT,
  StationCode VARCHAR(3) NOT NULL DEFAULT '',
  StationName VARCHAR(80) DEFAULT NULL,
  AlternateNames VARCHAR(1000) DEFAULT '',
  StationType VARCHAR(100) DEFAULT '' COMMENT 'rail or bus',
  CityCode VARCHAR(3) NOT NULL DEFAULT '',
  CountryCode VARCHAR(3) NOT NULL DEFAULT '',
  IcaoCode VARCHAR(4) DEFAULT NULL COMMENT 'ICAO код станции',
  Lat DOUBLE DEFAULT NULL,
  Lng DOUBLE DEFAULT NULL,
  TimeZoneLocation VARCHAR(64) DEFAULT NULL,
  TimeZoneID INT(11) DEFAULT NULL,
  LastUpdateDate DATETIME DEFAULT NULL,
  Gmt INT(11) DEFAULT NULL,
  PRIMARY KEY (StationCodeID),
  INDEX idx_Geo (Lat, Lng),
  INDEX idxCityCode (CityCode),
  INDEX Index_2 (StationCode),
  UNIQUE INDEX stationcode_stationcode_unique (StationCode),
  INDEX TimeZoneID (TimeZoneID)
)
ENGINE = INNODB
CHARACTER SET utf8
COLLATE utf8_general_ci
");
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql("drop table StationCode");

        $this->addSql("CREATE TABLE RailCode (
  RailCodeID INT(11) NOT NULL AUTO_INCREMENT,
  RailCode VARCHAR(3) NOT NULL DEFAULT '',
  RailName VARCHAR(80) DEFAULT NULL,
  AlternateNames VARCHAR(1000) DEFAULT '',
  CityCode VARCHAR(3) NOT NULL DEFAULT '',
  CountryCode VARCHAR(3) NOT NULL DEFAULT '',
  IcaoCode VARCHAR(4) DEFAULT NULL COMMENT 'ICAO код станции',
  Lat DOUBLE DEFAULT NULL,
  Lng DOUBLE DEFAULT NULL,
  TimeZoneLocation VARCHAR(64) DEFAULT NULL,
  TimeZoneID INT(11) DEFAULT NULL,
  LastUpdateDate DATETIME DEFAULT NULL,
  Gmt INT(11) DEFAULT NULL,
  PRIMARY KEY (RailCodeID),
  UNIQUE INDEX railcode_railcode_unique (RailCode),
  INDEX idx_Geo (Lat, Lng),
  INDEX idxCityCode (CityCode),
  INDEX Index_2 (RailCode),
  INDEX TimeZoneID (TimeZoneID)
)
ENGINE=InnoDB DEFAULT CHARSET=utf8");
    }
}
