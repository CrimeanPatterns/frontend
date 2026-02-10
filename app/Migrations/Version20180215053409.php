<?php

namespace AwardWallet\MainBundle\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
class Version20180215053409 extends AbstractMigration
{
    public function up(Schema $schema): void
    {
        $this->addSql('CREATE INDEX idx_name ON Airline (Name, Active DESC)');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('DROP INDEX idx_name ON Airline');
    }
}
