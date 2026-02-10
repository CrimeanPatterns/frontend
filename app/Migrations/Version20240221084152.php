<?php

declare(strict_types=1);

namespace AwardWallet\MainBundle\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20240221084152 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        $this->addSql("ALTER TABLE Provider ADD AccentColor VARCHAR(8) NULL COMMENT 'Акцентный цвет' AFTER FontColor");
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE Provider DROP COLUMN `AccentColor`');
    }
}
