<?php

namespace AwardWallet\MainBundle\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
class Version20131114142832 extends AbstractMigration
{
    public function up(Schema $schema): void
    {
        $this->addSql("ALTER TABLE `Usr` ADD `EmailOffers` TINYINT  NOT NULL  DEFAULT '1'  AFTER `EmailProductUpdates`");
    }

    public function down(Schema $schema): void
    {
        $this->addSql("ALTER TABLE `Usr` DROP `EmailOffers`");
    }
}
