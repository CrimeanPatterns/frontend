<?php

namespace AwardWallet\MainBundle\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

class Version20151008031030 extends AbstractMigration
{
    public function up(Schema $schema): void
    {
        $this->addSql("ALTER TABLE SocialAd ADD COLUMN NewDesignMode tinyint not null DEFAULT 0 COMMENT 'Реклама отображается в новом дизайне' AFTER `InternalNote`");
    }

    public function down(Schema $schema): void
    {
        $this->addSql("ALTER TABLE SocialAd DROP COLUMN NewDesignMode");
    }
}
