<?php

namespace AwardWallet\MainBundle\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
class Version20170125114403 extends AbstractMigration
{
    public function up(Schema $schema): void
    {
        $this->addSql("alter table AbSegment add column DepDateFlex tinyint(1) not null default 0");
        $this->addSql("alter table AbSegment add column ReturnDateFlex tinyint(1) not null default 0");
    }

    public function down(Schema $schema): void
    {
        $this->addSql("alter table AbSegment drop column DepDateFlex");
        $this->addSql("alter table AbSegment drop column ReturnDateFlex");
    }
}
