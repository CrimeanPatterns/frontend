<?php

namespace AwardWallet\MainBundle\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

class Version20180626132246 extends AbstractMigration
{
    public function up(Schema $schema): void
    {
        $this->addSql("
            ALTER TABLE `UserTip` ADD `ShowCount` MEDIUMINT(8) UNSIGNED NOT NULL DEFAULT '0' AFTER `CloseDate`
        ");
    }

    public function down(Schema $schema): void
    {
        $this->addSql("
            ALTER TABLE `UserTip` DROP `ShowCount`
        ");
    }
}
