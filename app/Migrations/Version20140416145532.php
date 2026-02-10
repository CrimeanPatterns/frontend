<?php

namespace AwardWallet\MainBundle\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/* reverted in Version20140420142316, commented out to skip time consuming useless table modifications on prod */
class Version20140416145532 extends AbstractMigration
{
    public function up(Schema $schema): void
    {
        //$this->addSql("alter table Account modify Pass varchar(250)");
    }

    public function down(Schema $schema): void
    {
        //$this->addSql("alter table Account modify Pass varchar(250) not null default ''");
    }
}
