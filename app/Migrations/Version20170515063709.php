<?php

namespace AwardWallet\MainBundle\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
class Version20170515063709 extends AbstractMigration
{
    public function up(Schema $schema): void
    {
        $this->addSql("alter table Invites modify Code varchar(20)");
    }

    public function down(Schema $schema): void
    {
        $this->addSql("alter table Invites modify Code varchar(10)");
    }
}
