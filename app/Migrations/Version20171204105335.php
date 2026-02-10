<?php

namespace AwardWallet\MainBundle\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
class Version20171204105335 extends AbstractMigration
{
    public function up(Schema $schema): void
    {
        $this->addSql("
            ALTER TABLE UserIP MODIFY IP VARCHAR(60) NOT NULL;
        ");
    }

    public function down(Schema $schema): void
    {
        $this->addSql("
            ALTER TABLE UserIP MODIFY IP VARCHAR(15) NOT NULL;
        ");
    }
}
