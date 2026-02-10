<?php

namespace AwardWallet\MainBundle\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

class Version20160519051410 extends AbstractMigration
{
    public function up(Schema $schema): void
    {
        $this->addSql("update UserAgent set AccessLevel = " . ACCESS_READ_ALL . " where AccessLevel = 1" /* old ACCESS_READ_BALANCE */);
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
    }
}
