<?php

namespace AwardWallet\MainBundle\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
class Version20170605080505 extends AbstractMigration
{
    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE `Provider` ADD `StatePrev` TINYINT(4) NULL DEFAULT NULL COMMENT \'Предыдущее значение state, на этапы mark broken-fixing\' AFTER `State`');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE `Provider` DROP `StatePrev`');
    }
}
