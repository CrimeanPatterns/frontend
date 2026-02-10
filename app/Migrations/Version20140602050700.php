<?php

namespace AwardWallet\MainBundle\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

class Version20140602050700 extends AbstractMigration
{
    public function up(Schema $schema): void
    {
        $this->addSql("create index idxAddress1 on ActivePropertyList(Address1)");
    }

    public function down(Schema $schema): void
    {
        $this->addSql("drop index idxAddress1 on ActivePropertyList");
    }
}
