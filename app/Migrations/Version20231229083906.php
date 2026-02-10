<?php

declare(strict_types=1);

namespace AwardWallet\MainBundle\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20231229083906 extends AbstractMigration
{
    private const TABLES = [
        'TripSegment',
        'Rental',
        'Restaurant',
        'Parking',
        'Reservation',
    ];

    public function up(Schema $schema): void
    {
        foreach (self::TABLES as $table) {
            $this->addSql("
                ALTER TABLE $table
                ADD COLUMN ShowAIWarning TINYINT(1) NOT NULL DEFAULT 1 COMMENT 'Показывать предупреждение о том, что данная резервация была запроцессена AI',
                ALGORITHM INSTANT
            ");
        }
    }

    public function down(Schema $schema): void
    {
        foreach (self::TABLES as $table) {
            $this->addSql("
                ALTER TABLE $table
                DROP COLUMN ShowAIWarning
            ");
        }
    }
}
