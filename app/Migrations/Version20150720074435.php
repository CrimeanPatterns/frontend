<?php

namespace AwardWallet\MainBundle\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

class Version20150720074435 extends AbstractMigration
{
    public function up(Schema $schema): void
    {
        $this->addSql("ALTER TABLE Account ADD COLUMN ErrorInternalNote text DEFAULT NULL COMMENT 'Пояснение к ErrorMessage (для внутреннего пользования)' AFTER `ErrorMessage`");
    }

    public function down(Schema $schema): void
    {
        $this->addSql("ALTER TABLE Account DROP COLUMN ErrorInternalNote");
    }
}
