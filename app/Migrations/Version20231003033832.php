<?php

declare(strict_types=1);

namespace AwardWallet\MainBundle\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20231003033832 extends AbstractMigration
{
    public function up(Schema $schema): void
    {
        $this->addSql("
            ALTER TABLE Lounge
                ADD COLUMN AttentionRequired TINYINT(1) NOT NULL DEFAULT 0 COMMENT 'Требуется ли внимание менеджера для проверки лаунджа',
                ADD COLUMN `State` JSON NULL COMMENT 'Важная информация для менеджера для проверки лаунджа',
                ADD INDEX Lounge_AttentionRequired (AttentionRequired)
        ");
    }

    public function down(Schema $schema): void
    {
        $this->addSql("
            ALTER TABLE Lounge
                DROP INDEX Lounge_AttentionRequired,
                DROP COLUMN AttentionRequired,
                DROP COLUMN `State`
        ");
    }
}
