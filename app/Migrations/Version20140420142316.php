<?php

namespace AwardWallet\MainBundle\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/* this migration will revert Version20140416145532 if it was executed */
class Version20140420142316 extends AbstractMigration
{
    public function up(Schema $schema): void
    {
        if (!$schema->getTable('Account')->getColumn('Pass')->getNotnull()) {
            $this->addSql("update Account set Pass = '' where Pass is null");
            $this->addSql("alter table Account modify Pass varchar(250) not null default ''");
        }
    }

    public function down(Schema $schema): void
    {
    }
}
