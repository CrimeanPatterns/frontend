<?php

namespace AwardWallet\MainBundle\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
class Version20170821121058 extends AbstractMigration
{
    public function up(Schema $schema): void
    {
        $this->addSql("alter table Param modify BigData mediumtext comment 'Если данные не влезают в поле Val'");
    }

    public function down(Schema $schema): void
    {
        $this->addSql("alter table Param modify BigData mediumtext not null");
    }
}
