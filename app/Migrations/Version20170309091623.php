<?php

namespace AwardWallet\MainBundle\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
class Version20170309091623 extends AbstractMigration
{
    public function up(Schema $schema): void
    {
        $this->addSql("ALTER TABLE `NotificationTemplate` ADD `DisplayDuration` INT(11)  NOT NULL  DEFAULT '0'  COMMENT 'Продолжительность показа пуша на экране'  AFTER `TTL`");
    }

    public function down(Schema $schema): void
    {
        $this->addSql("ALTER TABLE `NotificationTemplate` DROP `DisplayDuration`");
    }
}
