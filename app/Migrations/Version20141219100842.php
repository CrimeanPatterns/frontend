<?php

namespace AwardWallet\MainBundle\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

class Version20141219100842 extends AbstractMigration
{
    public function up(Schema $schema): void
    {
        $this->addSql("update Account set PassChangeDate = CreationDate where PassChangeDate is null");
        $this->addSql("alter table Account modify PassChangeDate datetime not null comment 'Дата изменения/первой установки пароля'");
    }

    public function down(Schema $schema): void
    {
        $this->addSql("alter table Account modify PassChangeDate datetime comment 'Дата изменения/первой установки пароля'");
    }
}
