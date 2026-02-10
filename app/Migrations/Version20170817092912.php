<?php

namespace AwardWallet\MainBundle\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

class Version20170817092912 extends AbstractMigration
{
    public function up(Schema $schema): void
    {
        $this->addSql("ALTER TABLE `Faq` ADD `EnglishOnly` TINYINT(1) NOT NULL DEFAULT '0' AFTER `Visible`");
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE `Faq` DROP `EnglishOnly`');
    }
}
