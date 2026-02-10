<?php

namespace AwardWallet\MainBundle\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

class Version20151119051233 extends AbstractMigration
{
    public function up(Schema $schema): void
    {
        $this->addSql("update OneTimeCode set CreationDate = '2000-01-01' where CreationDate is null");
        $this->addSql("alter table OneTimeCode modify CreationDate datetime not null comment 'Нужна для очистки старых кодов'");
    }

    public function down(Schema $schema): void
    {
        $this->addSql("alter table OneTimeCode modify CreationDate timestamp");
    }
}
