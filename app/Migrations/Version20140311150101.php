<?php

namespace AwardWallet\MainBundle\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
class Version20140311150101 extends AbstractMigration
{
    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('alter table BonusConversion DROP FOREIGN KEY `BonusConversion_ibfk_2`;');
        $this->addSql('alter table BonusConversion add CONSTRAINT `BonusConversion_ibfk_2` FOREIGN KEY (`AccountID`) REFERENCES `Account` (`AccountID`) ON DELETE SET NULL;');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('alter table BonusConversion DROP FOREIGN KEY `BonusConversion_ibfk_2`;');
        $this->addSql('alter table BonusConversion add CONSTRAINT `BonusConversion_ibfk_2` FOREIGN KEY (`AccountID`) REFERENCES `Account` (`AccountID`) ON DELETE CASCADE;');
    }
}
