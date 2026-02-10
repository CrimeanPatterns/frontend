<?php

namespace AwardWallet\MainBundle\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
class Version20171206050845 extends AbstractMigration
{
    public function up(Schema $schema): void
    {
        $this->addSql("alter table Session modify SessionID varchar(64)");
    }

    public function down(Schema $schema): void
    {
        $this->addSql("alter table Session modify SessionID varchar(32)");
    }
}
