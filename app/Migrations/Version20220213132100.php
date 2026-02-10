<?php

declare(strict_types=1);

namespace AwardWallet\MainBundle\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20220213132100 extends AbstractMigration
{
    public function up(Schema $schema): void
    {
        $this->addSql("
            ALTER TABLE Plan ADD COLUMN Notes VARCHAR(4000) NULL COMMENT 'Заметки' AFTER EndDate;
        ");
    }

    public function down(Schema $schema): void
    {
        $this->addSql("ALTER TABLE Plan DROP COLUMN Notes;");
    }
}
