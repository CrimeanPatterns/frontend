<?php

namespace AwardWallet\MainBundle\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

class Version20150813085310 extends AbstractMigration
{
    public function up(Schema $schema): void
    {
        $this->addSql("ALTER TABLE Account CHANGE ErrorInternalNote DebugInfo text");
    }

    public function down(Schema $schema): void
    {
        $this->addSql("ALTER TABLE Account CHANGE DebugInfo ErrorInternalNote text");
    }
}
