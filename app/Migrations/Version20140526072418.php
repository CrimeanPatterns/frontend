<?php

namespace AwardWallet\MainBundle\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
class Version20140526072418 extends AbstractMigration
{
    public function up(Schema $schema): void
    {
        $this->addSql("alter table ScanHistory add column ParsedType tinyint");
        $this->addSql("update ScanHistory set ParsedType = 1 where ParsedJson like '{\"Properties%'");
        $this->addSql("update ScanHistory set ParsedType = 2 where ParsedJson like '{\"Itineraries%'");
    }

    public function down(Schema $schema): void
    {
        $this->addSql("alter table ScanHistory drop column ParsedType");
    }
}
