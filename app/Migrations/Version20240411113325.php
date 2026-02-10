<?php

declare(strict_types=1);

namespace AwardWallet\MainBundle\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20240411113325 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        $this->addSql(/** @lang MySQL */ "ALTER TABLE `Provider` DROP COLUMN `RenewNote`");
    }

    public function down(Schema $schema): void
    {
        $this->addSql(/** @lang MySQL */ "ALTER TABLE `Provider` ADD COLUMN `RenewNote` TEXT NULL");
    }
}
