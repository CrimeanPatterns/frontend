<?php

declare(strict_types=1);

namespace AwardWallet\MainBundle\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20231102090541 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        if ($schema->hasTable('RAFlightRoute')) {
            $this->addSql(/** @lang MySQL */ "
            ALTER TABLE RAFlightRoute RENAME TO RAFlightSegment;
            ");
            $this->addSql(/** @lang MySQL */ "
            ALTER TABLE RAFlightSegment CHANGE COLUMN RAFlightRouteID RAFlightSegmentID INT AUTO_INCREMENT; 
            ");
            $this->addSql(/** @lang MySQL */ "
            ALTER TABLE RAFlightSegment AUTO_INCREMENT = 1; 
            ");
        }
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        if ($schema->hasTable('RAFlightSegment')) {
            $this->addSql(/** @lang MySQL */ "
            ALTER TABLE RAFlightSegment RENAME TO RAFlightRoute;
            ");
            $this->addSql(/** @lang MySQL */ "
            ALTER TABLE RAFlightRoute CHANGE COLUMN RAFlightSegmentID RAFlightRouteID INT AUTO_INCREMENT; 
            ");
            $this->addSql(/** @lang MySQL */ "
            ALTER TABLE RAFlightRoute AUTO_INCREMENT = 1; 
            ");
        }
    }
}
