<?php

declare(strict_types=1);

namespace AwardWallet\MainBundle\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20240212083908 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql(/** @lang MySQL */ "
            ALTER TABLE RAFlightSearchRoute
                ADD COLUMN ItineraryCOS VARCHAR(15) NULL COMMENT 'StandardItineraryCOS - вычисленный primaryCabin' AFTER Tickets;
        ");
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql(/** @lang MySQL */ "
            ALTER TABLE RAFlightSearchRoute
                DROP COLUMN ItineraryCOS;
        ");
    }
}
