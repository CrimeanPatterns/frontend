<?php

namespace AwardWallet\MainBundle\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

class Version20140627174008 extends AbstractMigration
{
    public function up(Schema $schema): void
    {
        $this->addSql("update Account set NextCheckPriority = 4 where NextCheckPriority = 6");
    }

    public function down(Schema $schema): void
    {
        $this->addSql("update Account set NextCheckPriority = 6 where NextCheckPriority = 4");
    }
}
