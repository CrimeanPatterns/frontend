<?php

declare(strict_types=1);

namespace AwardWallet\MainBundle\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20210318100237 extends AbstractMigration
{
    public function up(Schema $schema): void
    {
        foreach ($this->connection->fetchAll("select HotelBrandID, Patterns from HotelBrand where Patterns is not null") as $row) {
            $this->connection->executeUpdate("update HotelBrand set Patterns = ? where HotelBrandID = ?", [html_entity_decode($row['Patterns']), $row['HotelBrandID']]);
        }
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
    }
}
