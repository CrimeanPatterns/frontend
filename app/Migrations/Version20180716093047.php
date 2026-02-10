<?php

declare(strict_types=1);

namespace AwardWallet\MainBundle\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20180716093047 extends AbstractMigration
{
    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
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

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql("drop table RailCode");
    }
}
