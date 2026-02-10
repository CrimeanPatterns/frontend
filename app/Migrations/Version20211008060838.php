<?php

declare(strict_types=1);

namespace AwardWallet\MainBundle\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20211008060838 extends AbstractMigration
{
    public function up(Schema $schema): void
    {
        $this->addSql("ALTER TABLE LoungePage ADD IsMergable TINYINT DEFAULT 1 NOT NULL COMMENT 'Можно ли мержить' AFTER LocationChanged");
    }

    public function down(Schema $schema): void
    {
        $this->addSql("ALTER TABLE LoungePage DROP IsMergable");
    }
}
