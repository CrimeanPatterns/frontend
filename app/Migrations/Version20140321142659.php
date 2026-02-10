<?php

namespace AwardWallet\MainBundle\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

class Version20140321142659 extends AbstractMigration
{
    public function up(Schema $schema): void
    {
        $this->addSql("ALTER TABLE EmailStat CHANGE StatDate StatDate DATE NOT NULL;");
    }

    public function down(Schema $schema): void
    {
        $this->addSql("ALTER TABLE EmailStat CHANGE StatDate StatDate DATETIME NOT NULL;");
    }
}
