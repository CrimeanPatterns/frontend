<?php

namespace AwardWallet\MainBundle\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
class Version20151122094545 extends AbstractMigration
{
    public function up(Schema $schema): void
    {
        $this->addSql("ALTER TABLE `AbBookerInfo` ADD `AcceptChecks` TINYINT  UNSIGNED  NOT NULL  DEFAULT '0'  COMMENT 'Принимает ли букер чеки'  AFTER `DisableAd`");
        $this->addSql("UPDATE `AbBookerInfo` SET `AcceptChecks` = 1 WHERE `UserID` = 116000");
    }

    public function down(Schema $schema): void
    {
        $this->addSql("ALTER TABLE `AbBookerInfo` DROP `AcceptChecks`");
    }
}
