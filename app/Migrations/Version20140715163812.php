<?php

namespace AwardWallet\MainBundle\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

class Version20140715163812 extends AbstractMigration
{
    public function up(Schema $schema): void
    {
        $this->addSql("update AbMessage set Type = 0 where ImapMessageID is not null");
    }

    public function down(Schema $schema): void
    {
    }
}
