<?php

declare(strict_types=1);

namespace AwardWallet\MainBundle\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20180917134416 extends AbstractMigration
{
    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql("ALTER TABLE `StationCode`
  ADD COLUMN `CityName` VARCHAR(40) DEFAULT NULL,
  ADD COLUMN `AddressLine` VARCHAR(255) DEFAULT NULL,
  ADD COLUMN `State` VARCHAR(4) DEFAULT NULL COMMENT 'Код или короткое название штата',
  ADD COLUMN `StateName` VARCHAR(100) DEFAULT NULL COMMENT 'Полное название штата',
  ADD COLUMN `Country` VARCHAR(100) DEFAULT NULL,
  ADD COLUMN `PostalCode` VARCHAR(40) DEFAULT NULL,
  ADD COLUMN `LatOriginal` DOUBLE DEFAULT NULL,
  ADD COLUMN `LngOriginal` DOUBLE DEFAULT NULL;");
        $this->addSql("UPDATE `StationCode` SET `LatOriginal` = `Lat`, `LngOriginal` = `Lng`");
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql("UPDATE `StationCode` SET `Lat` = `LatOriginal`, `Lng` = `LngOriginal`");
        $this->addSql("ALTER TABLE `StationCode`
  DROP COLUMN `CityName`,
  DROP COLUMN `AddressLine`,
  DROP COLUMN `State`,
  DROP COLUMN `StateName`,
  DROP COLUMN `Country`,
  DROP COLUMN `PostalCode`,
  DROP COLUMN `LatOriginal`,
  DROP COLUMN `LngOriginal`;");
    }
}
