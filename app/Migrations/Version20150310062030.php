<?php

namespace AwardWallet\MainBundle\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
class Version20150310062030 extends AbstractMigration
{
    public function up(Schema $schema): void
    {
        $this->addSql("ALTER TABLE `Usr` ADD `GoogleAuthRecoveryCode` VARCHAR(250) DEFAULT NULL COMMENT 'рекавери код для сброса двухфакторной аутентификации' AFTER `GoogleAuthSecret`");
    }

    public function down(Schema $schema): void
    {
        $this->addSql("ALTER TABLE `Usr` DROP `GoogleAuthRecoveryCode`");
    }
}
