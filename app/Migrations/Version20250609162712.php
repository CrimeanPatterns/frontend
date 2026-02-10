<?php

declare(strict_types=1);

namespace AwardWallet\MainBundle\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20250609162712 extends AbstractMigration
{
    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE Usr ADD KEY idxAccountLevel (AccountLevel)');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE Usr DROP KEY idxAccountLevel');
    }
}
