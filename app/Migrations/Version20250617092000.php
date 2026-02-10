<?php

declare(strict_types=1);

namespace AwardWallet\MainBundle\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20250617092000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        $this->addSql(/** @lang MySQL */ "
            ALTER TABLE `Provider`
                ADD COLUMN `ClientSideLastFixDate` DATETIME NULL COMMENT 'Used to count errors on Provider Status',
                ADD COLUMN `ServerSideLastFixDate` DATETIME NULL COMMENT 'Used to count errors on Provider Status';");
    }

    public function down(Schema $schema): void
    {
        $this->addSql(/** @lang MySQL */ "
            ALTER TABLE `Provider`
                DROP COLUMN `ClientSideLastFixDate`,
                DROP COLUMN `ServerSideLastFixDate`;");
    }
}
