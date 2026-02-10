<?php

namespace AwardWallet\MainBundle\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

class Version20151110054638 extends AbstractMigration
{
    public function up(Schema $schema): void
    {
        $this->addSql("alter table AccountHistory modify Description varchar(4000) COMMENT 'Описание транзакции'");
    }

    public function down(Schema $schema): void
    {
        $this->addSql("alter table AccountHistory modify Description varchar(4000) NOT NULL DEFAULT '' COMMENT ' Описание транзакции'");
    }
}
