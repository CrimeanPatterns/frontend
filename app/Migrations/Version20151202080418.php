<?php

namespace AwardWallet\MainBundle\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
class Version20151202080418 extends AbstractMigration
{
    public function up(Schema $schema): void
    {
        $this->addSql("ALTER TABLE `Usr` DROP `DisableExtension`");
    }

    public function down(Schema $schema): void
    {
        $this->addSql("ALTER TABLE `Usr` ADD `DisableExtension` TINYINT  NOT NULL  DEFAULT '0'  AFTER `Mismanagement`");
    }
}
