<?php

namespace AwardWallet\MainBundle\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
class Version20170607153208 extends AbstractMigration
{
    public function up(Schema $schema): void
    {
        $this->addSql("alter table Account drop UseFrugalAutologin");
        $this->addSql("alter table Provider drop AutoLoginPartner");
        $this->addSql("drop table ProviderPartner");
    }

    public function down(Schema $schema): void
    {
    }
}
