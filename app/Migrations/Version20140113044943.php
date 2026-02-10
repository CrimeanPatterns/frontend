<?php

namespace AwardWallet\MainBundle\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
class Version20140113044943 extends AbstractMigration
{
    public function up(Schema $schema): void
    {
        $this->addSql("ALTER TABLE `Answer` ADD `CreateDate` datetime COMMENT 'Дата ввода ответа';");
        $this->addSql("ALTER TABLE `Answer` ADD `Valid` TINYINT NOT NULL DEFAULT '1' COMMENT 'Валидность ответа'");
    }

    public function down(Schema $schema): void
    {
        $this->addSql("ALTER TABLE `Answer` DROP `CreateDate`");
        $this->addSql("ALTER TABLE `Answer` DROP `Valid`");
    }
}
