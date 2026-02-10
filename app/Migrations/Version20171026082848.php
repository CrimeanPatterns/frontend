<?php

namespace AwardWallet\MainBundle\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
class Version20171026082848 extends AbstractMigration
{
    public function up(Schema $schema): void
    {
        $this->addSql("ALTER TABLE `AccountHistory` ADD INDEX (`Multiplier`);");
    }

    public function down(Schema $schema): void
    {
        $this->addSql("ALTER TABLE `AccountHistory` DROP INDEX `Multiplier`;");
    }
}
