<?php

namespace AwardWallet\MainBundle\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

class Version20170905000000 extends AbstractMigration
{
    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE `Trip` ADD `Phone` VARCHAR(80) NULL DEFAULT NULL AFTER `UserID`');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE `Trip` DROP `Phone`');
    }
}
