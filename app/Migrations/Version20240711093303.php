<?php

declare(strict_types=1);

namespace AwardWallet\MainBundle\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20240711093303 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // add bool Border_LM and Border_DM columns to Provider table after AccentColor column
        $this->addSql('ALTER TABLE Provider ADD Border_LM TINYINT(1) NOT NULL AFTER AccentColor');
        $this->addSql('ALTER TABLE Provider ADD Border_DM TINYINT(1) NOT NULL AFTER Border_LM');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
    }
}
