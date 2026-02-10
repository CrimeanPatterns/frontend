<?php

namespace AwardWallet\MainBundle\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
class Version20151016142329 extends AbstractMigration
{
    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql("UPDATE AbMessage m LEFT JOIN AbMessageColor c ON c.AbMessageColorID = m.ColorID SET m.ColorID = NULL WHERE m.ColorID IS NOT NULL AND c.AbMessageColorID IS NULL");
        $this->addSql("ALTER TABLE AbMessage ADD CONSTRAINT `FK_AbMColourID` FOREIGN KEY (`ColorID`) REFERENCES `AbMessageColor` (`AbMessageColorID`) ON DELETE SET NULL");
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql("ALTER TABLE AbMessage DROP FOREIGN KEY FK_AbMColourID");
        $this->addSql("DROP INDEX FK_AbMColourID ON AbMessage;");
    }
}
