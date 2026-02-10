<?php

namespace AwardWallet\MainBundle\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
class Version20131106102001 extends AbstractMigration
{
    public function up(Schema $schema): void
    {
        $this->addSql('RENAME TABLE AbRequestRead TO AbRequestMark;');
        $this->addSql('ALTER TABLE AbRequestMark CHANGE AbRequestReadID AbRequestMarkID INT(11)  NOT NULL  AUTO_INCREMENT;');
        $this->addSql("ALTER TABLE AbRequestMark ADD IsRead TINYINT  NOT NULL  DEFAULT '1'  AFTER RequestID;");
    }

    public function down(Schema $schema): void
    {
        $this->addSql('RENAME TABLE AbRequestMark TO AbRequestRead;');
        $this->addSql('ALTER TABLE AbRequestRead DROP IsRead;');
        $this->addSql('ALTER TABLE AbRequestRead CHANGE AbRequestMarkID AbRequestReadID INT(11)  NOT NULL  AUTO_INCREMENT;');
    }
}
