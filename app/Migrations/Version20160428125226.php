<?php

namespace AwardWallet\MainBundle\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
class Version20160428125226 extends AbstractMigration
{
    public function up(Schema $schema): void
    {
        $this->addSql("drop table ProviderStatusHistory");
    }

    public function down(Schema $schema): void
    {
    }
}
