<?php

namespace AwardWallet\MainBundle\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
class Version20131106064153 extends AbstractMigration
{
    public function up(Schema $schema): void
    {
        $this->addSql('UPDATE AbRequest SET CreateDate = NOW() WHERE CreateDate IS NULL;');
        $this->addSql('ALTER TABLE AbRequest CHANGE CreateDate CreateDate DATETIME  NOT NULL;');
        $this->addSql('ALTER TABLE AbRequest ADD LastUpdateDate DATETIME  NOT NULL  AFTER CreateDate;');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE AbRequest DROP LastUpdateDate;');
        $this->addSql('ALTER TABLE AbRequest CHANGE CreateDate CreateDate DATETIME  NULL  DEFAULT NULL;');
    }
}
