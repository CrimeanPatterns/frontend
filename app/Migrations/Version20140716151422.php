<?php

namespace AwardWallet\MainBundle\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

class Version20140716151422 extends AbstractMigration
{
    public function up(Schema $schema): void
    {
        $this->addSql("ALTER TABLE `AbMessage` MODIFY `Type` int NOT NULL DEFAULT '0';");
    }

    public function down(Schema $schema): void
    {
        $this->addSql("ALTER TABLE `AbMessage` MODIFY `Type` int;");
    }
}
