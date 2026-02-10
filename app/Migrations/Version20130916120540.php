<?php

namespace AwardWallet\MainBundle\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your need!
 */
class Version20130916120540 extends AbstractMigration
{
    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql("alter table AAShare change column `Date` `CountDate` date not null");
        $this->addSql("alter table AAShare add unique key CountDateKey (CountDate)");
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql("alter table AAShare drop key CountDateKey");
        $this->addSql("alter table AAShare change column `CountDate` `Date` date not null");
    }
}
