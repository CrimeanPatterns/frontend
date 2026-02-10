<?php

namespace AwardWallet\MainBundle\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
class Version20141021092704 extends AbstractMigration
{
    public function up(Schema $schema): void
    {
        $this->addSql("drop index Email on UserEmail");
    }

    public function down(Schema $schema): void
    {
        $this->addSql("alter table UserEmail add unique (Email)");
    }
}
