<?php

namespace AwardWallet\MainBundle\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
class Version20140122054623 extends AbstractMigration
{
    public function up(Schema $schema): void
    {
        $this->addSql("ALTER TABLE `AbSegment` CHANGE `DepDateFrom` `DepDateFrom` DATETIME  NULL  COMMENT 'Дата вылета начальная';");
        $this->addSql("ALTER TABLE `AbSegment` CHANGE `DepDateTo` `DepDateTo` DATETIME  NULL  COMMENT 'Дата вылета конечная';");
    }

    public function down(Schema $schema): void
    {
        $this->addSql("ALTER TABLE `AbSegment` CHANGE `DepDateFrom` `DepDateFrom` DATETIME NOT NULL  COMMENT 'Дата вылета начальная';");
        $this->addSql("ALTER TABLE `AbSegment` CHANGE `DepDateTo` `DepDateTo` DATETIME NOT NULL  COMMENT 'Дата вылета конечная';");
    }
}
