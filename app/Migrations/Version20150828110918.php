<?php

namespace AwardWallet\MainBundle\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

class Version20150828110918 extends AbstractMigration
{
    public function up(Schema $schema): void
    {
        $this->addSql("ALTER TABLE Account ADD COLUMN ErrorReason text DEFAULT NULL COMMENT 'Пояснение к ErrorMessage (для партнёров)' AFTER `ErrorMessage`");
    }

    public function down(Schema $schema): void
    {
        $this->addSql("ALTER TABLE Account DROP COLUMN ErrorReason");
    }
}
