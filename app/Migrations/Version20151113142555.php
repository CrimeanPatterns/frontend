<?php

namespace AwardWallet\MainBundle\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

class Version20151113142555 extends AbstractMigration
{
    public function up(Schema $schema): void
    {
        $this->addSql("UPDATE Usr set Region = 'en_US' WHERE DateFormat = 1");
        $this->addSql("UPDATE Usr set Region = 'en_GB' WHERE DateFormat = 2");
    }

    public function down(Schema $schema): void
    {
    }
}
