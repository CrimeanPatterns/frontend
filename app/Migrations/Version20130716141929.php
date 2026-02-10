<?php

namespace AwardWallet\MainBundle\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your need!
 */
class Version20130716141929 extends AbstractMigration
{
    public function up(Schema $schema): void
    {
        $this->addSql("alter table IncomeTransaction add Description varchar(2000) NOT NULL DEFAULT '';");
    }

    public function down(Schema $schema): void
    {
        $this->addSql('alter table IncomeTransaction drop column Description');
    }
}
