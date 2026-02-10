<?php

namespace AwardWallet\MainBundle\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
class Version20151216111118 extends AbstractMigration
{
    public function up(Schema $schema): void
    {
        $this->addSql('alter table ScanHistory add column EmailMessageID int not null');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('alter table ScanHistory drop column EmailMessageID');
    }
}
