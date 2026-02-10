<?php

namespace AwardWallet\MainBundle\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

class Version20160712221108 extends AbstractMigration
{
    public function up(Schema $schema): void
    {
        $this->addSql("ALTER TABLE `BusinessInfo` ADD COLUMN `APIKey` varchar(128) NOT NULL DEFAULT '' AFTER `TrialEndDate`");
        $this->addSql("ALTER TABLE `BusinessInfo` ADD COLUMN `APICallbackUrl` varchar(1024) NOT NULL DEFAULT '' AFTER `APIKey`");
        $this->addSql("ALTER TABLE `BusinessInfo` ADD COLUMN `APIAllowIp` text NOT NULL DEFAULT '' AFTER `APICallbackUrl`");
    }

    public function down(Schema $schema): void
    {
        $this->addSql("alter table `BusinessInfo` drop column `APIAllowIp`");
        $this->addSql("alter table `BusinessInfo` drop column `APICallbackUrl`");
        $this->addSql("alter table `BusinessInfo` drop column `APIKey`");
    }
}
