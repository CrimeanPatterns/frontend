<?php

namespace AwardWallet\MainBundle\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
class Version20170717145654 extends AbstractMigration
{
    public function up(Schema $schema): void
    {
        $this->addSql("ALTER TABLE UserAgent ADD FULLTEXT INDEX UserAgent_FirstName_MidName_LastName_findex (FirstName, MidName, LastName)");
        $this->addSql("ALTER TABLE Usr ADD FULLTEXT INDEX Usr_FirstName_MidName_LastName_findex (FirstName, MidName, LastName)");
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE UserAgent DROP INDEX UserAgent_FirstName_MidName_LastName_findex');
        $this->addSql('ALTER TABLE Usr DROP INDEX Usr_FirstName_MidName_LastName_findex');
    }
}
