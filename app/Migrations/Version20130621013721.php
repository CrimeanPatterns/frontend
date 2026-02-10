<?php

namespace AwardWallet\MainBundle\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your need!
 */
class Version20130621013721 extends AbstractMigration
{
    public function up(Schema $schema): void
    {
        $this->addSql("alter table PasswordVault modify Login varchar(80)");
        $this->addSql("alter table PasswordVault modify Login2 varchar(120)");
        $this->addSql("alter table PasswordVault modify Pass varchar(250)");
    }

    public function down(Schema $schema): void
    {
        $this->addSql("alter table PasswordVault modify Login varchar(40)");
        $this->addSql("alter table PasswordVault modify Login2 varchar(40)");
        $this->addSql("alter table PasswordVault modify Pass varchar(40)");
    }
}
