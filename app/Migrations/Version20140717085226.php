<?php

namespace AwardWallet\MainBundle\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
class Version20140717085226 extends AbstractMigration
{
    public function up(Schema $schema): void
    {
        $this->addSql("alter table UserEmail add column ScanAccounts tinyint not null default 0");
    }

    public function down(Schema $schema): void
    {
        $this->addSql("alter table UserEmail drop column ScanAccounts");
    }
}
