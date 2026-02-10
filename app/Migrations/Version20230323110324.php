<?php

declare(strict_types=1);

namespace AwardWallet\MainBundle\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20230323110324 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        /*
          Here we use Point type with structure as Point(Lng, Lat) — first Longitude (for X), then Latitude (Y).
          ST_SRID(Point(Lng, Lat), 4326) — sets ST_SRID (Spatial Reference System Identifier) to 4326, which is WGS84, used in GPS. https://epsg.io/4326
         */
        $this->addSql("ALTER TABLE ZipCode ADD COLUMN `Point` POINT GENERATED ALWAYS AS (ST_SRID(Point(Lng, Lat), 4326)) STORED SRID 4326 NOT NULL");
        $this->addSql("ALTER TABLE ZipCode ADD SPATIAL INDEX `ZipCode_Point` (`Point`)");
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE ZipCode DROP INDEX ZipCode_Point');
        $this->addSql('ALTER TABLE ZipCode DROP COLUMN `Point`');
    }
}
