<?php

declare(strict_types=1);

namespace AwardWallet\MainBundle\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20210725123446 extends AbstractMigration
{
    public function up(Schema $schema): void
    {
        $this->addSql("ALTER TABLE LoungePage DROP UpdateTerminalDate");
    }

    public function down(Schema $schema): void
    {
        $this->addSql("ALTER TABLE LoungePage ADD UpdateTerminalDate DATETIME DEFAULT CURRENT_TIMESTAMP NOT NULL COMMENT 'Дата последнего обновления терминала или гейта'");
    }
}
