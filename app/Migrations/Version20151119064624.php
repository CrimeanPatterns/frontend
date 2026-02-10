<?php

namespace AwardWallet\MainBundle\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

class Version20151119064624 extends AbstractMigration
{
    public function up(Schema $schema): void
    {
        $this->addSql("update Provider set Engine = " . PROVIDER_ENGINE_CURL . " where Engine = 0");
    }

    public function down(Schema $schema): void
    {
    }
}
