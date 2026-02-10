<?php

namespace AwardWallet\MainBundle\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
class Version20150924111705 extends AbstractMigration
{
    public function up(Schema $schema): void
    {
        $this->addSql('alter table UserEmail add column LastProgress int');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('alter table UserEmail drop column LastProgress');
    }
}
