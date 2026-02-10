<?php

namespace AwardWallet\MainBundle\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
class Version20150128060541 extends AbstractMigration
{
    public function up(Schema $schema): void
    {
        $this->addSql("UPDATE Provider SET FAQ = NULL WHERE FAQ NOT IN(SELECT FaqID FROM Faq)");
        $this->addSql("ALTER TABLE Provider ADD FOREIGN KEY FK_FAQ (FAQ) REFERENCES Faq(FaqID) ON DELETE SET NULL");
    }

    public function down(Schema $schema): void
    {
        $this->addSql("ALTER TABLE Provider DROP FOREIGN KEY Provider_ibfk_3");
    }
}
