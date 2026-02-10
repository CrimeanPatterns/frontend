<?php

declare(strict_types=1);

namespace AwardWallet\MainBundle\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20180824120224 extends AbstractMigration
{
    public function up(Schema $schema): void
    {
        $this->addSql("ALTER TABLE `MasterSlaveCategoryReport` ADD `Counter` INT  NOT NULL  DEFAULT '1' AFTER `SlaveCategoryID`;");
    }

    public function down(Schema $schema): void
    {
        $this->addSql("ALTER TABLE `MasterSlaveCategoryReport` DROP `Counter`;");
    }
}
