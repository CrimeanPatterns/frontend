<?php

namespace AwardWallet\MainBundle\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
class Version20140430094101 extends AbstractMigration
{
    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql("alter table AAMembership add column UserID int unsigned");
        $this->addSql("alter table AAMembership add column AccountID int unsigned");
        $this->addSql("alter table AAMembership add column ProviderID int");
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql("alter table AAMembership drop column AccountID");
        $this->addSql("alter table AAMembership drop column ProviderID");
        $this->addSql("alter table AAMembership drop column UserID");
    }
}
