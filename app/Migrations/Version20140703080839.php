<?php

namespace AwardWallet\MainBundle\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

class Version20140703080839 extends AbstractMigration
{
    public function up(Schema $schema): void
    {
        $this->addSql("alter table Provider drop SupportPhone");
        $this->addSql("alter table Provider add HasSupportPhones tinyint not null default 1 comment 'Есть ли телефоны поддержки'");
    }

    public function down(Schema $schema): void
    {
        $this->addSql("alter table Provider drop HasSupportPhones");
        $this->addSql("alter table Provider add SupportPhone varchar(20)");
    }
}
