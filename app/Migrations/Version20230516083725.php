<?php

declare(strict_types=1);

namespace AwardWallet\MainBundle\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20230516083725 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql(/** @lang MySQL */"ALTER TABLE `Provider` ADD COLUMN `ThrottleAllChecks` TINYINT(4) NOT NULL DEFAULT 0 COMMENT 'Tроттлить все проверки по провайдеру';");
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql(/** @lang MySQL */"ALTER TABLE `Provider` DROP COLUMN `ThrottleAllChecks`;");
    }
}
