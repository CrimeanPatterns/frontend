<?php

namespace AwardWallet\MainBundle\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
class Version20171204090910 extends AbstractMigration
{
    public function up(Schema $schema): void
    {
        $this->addSql("
            ALTER TABLE `Provider` ADD `CanCheckPastItinerary` INT(11)  NOT NULL  DEFAULT '0'  COMMENT 'если возможен парсинг резерваций из прошлого, то нужно поставить true\\n*Value*:\\ntrue/false'  AFTER `CanCheckItinerary`;
        ");
    }

    public function down(Schema $schema): void
    {
        $this->addSql("
            ALTER TABLE `Provider` DROP `CanCheckPastItinerary`;
        ");
    }
}
