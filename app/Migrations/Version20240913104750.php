<?php

declare(strict_types=1);

namespace AwardWallet\MainBundle\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20240913104750 extends AbstractMigration
{
    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE Provider ADD COLUMN AwardChangePolicy TEXT NULL');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('
            ALTER TABLE Provider
            DROP COLUMN AwardChangePolicy
        ');
    }
}
