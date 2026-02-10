<?php

namespace AwardWallet\MainBundle\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
class Version20150210114318 extends AbstractMigration
{
    public function up(Schema $schema): void
    {
        $this->addSql("ALTER TABLE Usr MODIFY GoogleAuthSecret VARCHAR(250) DEFAULT NULL COMMENT 'секрет для двухфакторной аутентификации'");
    }

    public function down(Schema $schema): void
    {
        $this->addSql("ALTER TABLE Usr MODIFY GoogleAuthSecret VARCHAR(16) DEFAULT NULL COMMENT 'секрет для двухфакторной аутентификации'");
    }
}
