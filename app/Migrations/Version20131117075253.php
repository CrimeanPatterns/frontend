<?php

namespace AwardWallet\MainBundle\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
class Version20131117075253 extends AbstractMigration
{
    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql("ALTER TABLE  `Deal` ADD  `Source` VARCHAR( 50 ) NOT NULL DEFAULT  ''");
        $this->addSql("ALTER TABLE  `Deal` ADD  `SourceID` VARCHAR( 20 ) NOT NULL DEFAULT  ''");
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql("ALTER TABLE  `Deal` DROP  `SourceID`");
        $this->addSql("ALTER TABLE  `Deal` DROP  `Source`");
    }
}
