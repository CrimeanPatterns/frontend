<?php

namespace AwardWallet\MainBundle\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your need!
 */
class Version20130807151706 extends AbstractMigration
{
    public function up(Schema $schema): void
    {
        $this->addSql('alter table `Account` add column `NextCheckPriority` int not null default 5 after `QueueDate`');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('alter table `Account` drop `NextCheckPriority`');
    }
}
