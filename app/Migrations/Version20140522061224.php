<?php

namespace AwardWallet\MainBundle\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
class Version20140522061224 extends AbstractMigration
{
    public function up(Schema $schema): void
    {
        $this->addSql("ALTER TABLE AbPassenger ADD UserAgentID INT(11)  NULL  DEFAULT NULL  AFTER RequestID;");
        $this->addSql("ALTER TABLE AbPassenger ADD CONSTRAINT FK_AbPUserAgentID FOREIGN KEY (UserAgentID) REFERENCES UserAgent (UserAgentID) ON DELETE SET NULL ON UPDATE CASCADE;");
    }

    public function down(Schema $schema): void
    {
        $this->addSql("ALTER TABLE AbPassenger DROP FOREIGN KEY FK_AbPUserAgentID;");
        $this->addSql("ALTER TABLE AbPassenger DROP UserAgentID;");
    }
}
