<?php

namespace AwardWallet\MainBundle\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
class Version20170120120411 extends AbstractMigration
{
    public function up(Schema $schema): void
    {
        $this->addSql("alter table UserAgent add MidName varchar(30)");
    }

    public function down(Schema $schema): void
    {
        $this->addSql("alter table UserAgent drop MidName");
    }
}
