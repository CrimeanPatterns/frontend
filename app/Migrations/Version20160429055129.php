<?php

namespace AwardWallet\MainBundle\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
class Version20160429055129 extends AbstractMigration
{
    public function up(Schema $schema): void
    {
        $this->addSql("drop table StayNight");
    }

    public function down(Schema $schema): void
    {
    }
}
