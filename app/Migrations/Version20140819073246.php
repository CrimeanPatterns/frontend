<?php

namespace AwardWallet\MainBundle\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
class Version20140819073246 extends AbstractMigration
{
    public function up(Schema $schema): void
    {
        $this->addSql("ALTER TABLE `AbRequest` ADD `CabinEconomy` TINYINT(1)  NOT NULL  COMMENT 'Эконом класс (опция)'  AFTER `CabinBusiness`;");
    }

    public function down(Schema $schema): void
    {
        $this->addSql("ALTER TABLE `AbRequest` DROP `CabinEconomy`;");
    }
}
