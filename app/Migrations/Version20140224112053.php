<?php

namespace AwardWallet\MainBundle\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
class Version20140224112053 extends AbstractMigration
{
    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE File MODIFY Filename VARCHAR(16) NOT NULL');
        $this->addSql('ALTER TABLE File MODIFY Resource VARCHAR(28) NOT NULL');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE File MODIFY Filename VARCHAR(128) NOT NULL');
        $this->addSql('ALTER TABLE File MODIFY Resource VARCHAR(255) NOT NULL');
    }
}
