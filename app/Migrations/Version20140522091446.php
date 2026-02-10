<?php

namespace AwardWallet\MainBundle\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
class Version20140522091446 extends AbstractMigration
{
    public function up(Schema $schema): void
    {
        $this->addSql("alter table UserEmailToken add column TokenType tinyint not null default 1, modify Token varchar(1000)");
    }

    public function down(Schema $schema): void
    {
        $this->addSql("alter table UserEmailToken drop column TokenType");
    }
}
