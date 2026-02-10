<?php

declare(strict_types=1);

namespace AwardWallet\MainBundle\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20200806162315 extends AbstractMigration
{
    public function up(Schema $schema): void
    {
        $this->addSql("
            ALTER TABLE Currency ADD Plural VARCHAR(250) NOT NULL DEFAULT '' COMMENT 'Плюрализация' AFTER Name;
            UPDATE Currency SET Plural = Name;
        ");
    }

    public function down(Schema $schema): void
    {
        $this->addSql("ALTER TABLE Currency DROP Plural");
    }
}
