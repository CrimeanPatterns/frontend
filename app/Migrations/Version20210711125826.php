<?php

declare(strict_types=1);

namespace AwardWallet\MainBundle\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20210711125826 extends AbstractMigration
{
    public function up(Schema $schema): void
    {
        $this->addSql("
            DELETE FROM AccountBalance WHERE Balance IS NULL;
            ALTER TABLE AccountBalance MODIFY Balance DECIMAL(18,2) NOT NULL COMMENT 'Баланс';
        ");
    }

    public function down(Schema $schema): void
    {
        $this->addSql("ALTER TABLE AccountBalance MODIFY Balance DECIMAL(18,2) NULL COMMENT 'Баланс'");
    }
}
