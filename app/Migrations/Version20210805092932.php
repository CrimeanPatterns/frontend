<?php

declare(strict_types=1);

namespace AwardWallet\MainBundle\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20210805092932 extends AbstractMigration
{
    public function up(Schema $schema): void
    {
        $this->addSql("ALTER TABLE LoungePage DROP Status");
    }

    public function down(Schema $schema): void
    {
        $this->addSql("ALTER TABLE LoungePage ADD Status TINYINT DEFAULT 1 NOT NULL COMMENT 'Статус. 0 - неактивная запись / 1 - Актуальная запись' AFTER AmexPlatinumAccess");
    }
}
