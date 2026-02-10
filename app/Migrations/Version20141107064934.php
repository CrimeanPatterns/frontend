<?php

namespace AwardWallet\MainBundle\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

class Version20141107064934 extends AbstractMigration
{
    public function up(Schema $schema): void
    {
        $this->addSql("alter table PasswordVault
			add Partner varchar(20) not null default '' comment 'Партнер (WSDL)',
			modify CreationDate datetime not null,
			add Answers varchar(4000) comment 'Ответы на вопросы, JSON'");
        $this->addSql("create index idxSearch on PasswordVault(Partner, Login, Login2, ProviderID)");
        $this->addSql("create index idxDate on PasswordVault(ExpirationDate)");
    }

    public function down(Schema $schema): void
    {
        $this->addSql("alter table PasswordVault drop index idxSearch");
        $this->addSql("alter table PasswordVault drop index idxDate");
        $this->addSql("alter table PasswordVault drop Partner");
        $this->addSql("alter table PasswordVault drop Answers");
    }
}
