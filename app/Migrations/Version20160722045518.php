<?php

namespace AwardWallet\MainBundle\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

class Version20160722045518 extends AbstractMigration
{
    public function up(Schema $schema): void
    {
        $this->addSql("ALTER TABLE `BusinessInfo` ADD COLUMN `APIEnabled` tinyint(1) NOT NULL DEFAULT '0' AFTER `TrialEndDate`");
    }

    public function down(Schema $schema): void
    {
        $this->addSql("alter table `BusinessInfo` drop column `APIEnabled`");
    }
}
