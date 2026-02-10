<?php

namespace AwardWallet\MainBundle\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
class Version20131010055543 extends AbstractMigration
{
    public function up(Schema $schema): void
    {
        $this->addSql("alter table ProviderInputOption modify Code varchar(80) not null");
    }

    public function down(Schema $schema): void
    {
        $this->addSql("alter table ProviderInputOption modify Code varchar(40) not null");
    }
}
