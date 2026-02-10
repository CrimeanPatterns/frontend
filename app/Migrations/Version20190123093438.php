<?php

declare(strict_types=1);

namespace AwardWallet\MainBundle\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20190123093438 extends AbstractMigration
{
    public function up(Schema $schema): void
    {
        $this->addSql("
            ALTER TABLE `Merchant` 
            ADD `DisplayName` varchar(250) NULL DEFAULT NULL COMMENT 'Имя для отображения на сайте' AFTER `Name`,
            ADD `DetectPriority` SMALLINT NULL DEFAULT NULL COMMENT 'Приоритет при детекте по Patterns' AFTER `Patterns`;
        ");
    }

    public function down(Schema $schema): void
    {
        $this->addSql("
            ALTER TABLE `MerchantReport` 
            DROP COLUMN `DisplayName`,
            DROP COLUMN `DetectPriority`;
        ");

        // this down() migration is auto-generated, please modify it to your needs
    }
}
