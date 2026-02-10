<?php

namespace AwardWallet\MainBundle\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
class Version20170822074040 extends AbstractMigration
{
    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE `Usr` DROP COLUMN NewsDate');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE `Usr` ADD NewsDate datetime');
    }
}
