<?php

declare(strict_types=1);

namespace AwardWallet\MainBundle\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20240415065756 extends AbstractMigration
{
    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE Lounge ADD OpeningHoursAi JSON DEFAULT NULL AFTER OpeningHours');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE Lounge DROP COLUMN OpeningHoursAi');
    }
}
