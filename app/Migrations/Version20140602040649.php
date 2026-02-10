<?php

namespace AwardWallet\MainBundle\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

class Version20140602040649 extends AbstractMigration
{
    public function up(Schema $schema): void
    {
        $this->addSql("create index idxName on ActivePropertyList(Name)");
    }

    public function down(Schema $schema): void
    {
        $this->addSql("drop index idxName on ActivePropertyList");
    }
}
