<?php

namespace AwardWallet\MainBundle\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

class Version20140805052242 extends AbstractMigration
{
    public function up(Schema $schema): void
    {
        $this->addSql("alter table Usr drop CameFromTracked");
    }

    public function down(Schema $schema): void
    {
        $this->addSql("alter table Usr add CameFromTracked int");
    }
}
