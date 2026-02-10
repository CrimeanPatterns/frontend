<?php

namespace AwardWallet\MainBundle\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

class Version20140814142722 extends AbstractMigration
{
    public function up(Schema $schema): void
    {
        $this->addSql("alter table Provider add CanCheckFiles tinyint not null default 0 comment 'Можем ли собирать файлы с этого провайдера'");
    }

    public function down(Schema $schema): void
    {
        $this->addSql("alter table Provider drop CanCheckFiles");
    }
}
