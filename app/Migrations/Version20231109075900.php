<?php

declare(strict_types=1);

namespace AwardWallet\MainBundle\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20231109075900 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        $this->addSql(/** @lang MySQL */ "
            ALTER TABLE `Account`
                ADD COLUMN `IsArchived` TINYINT(1) DEFAULT 0 NOT NULL COMMENT 'Флаг, показывающий, находится ли аккаунт в архиве' AFTER `IsActiveTab`;
            ALTER TABLE `ProviderCoupon`
                ADD COLUMN `IsArchived` TINYINT(1) DEFAULT 0 NOT NULL COMMENT 'Флаг, показывающий, находится ли купон в архиве';");
    }

    public function down(Schema $schema): void
    {
        $this->addSql(/** @lang MySQL */ "
            ALTER TABLE `Account`
                DROP COLUMN `IsArchived`;
            ALTER TABLE `ProviderCoupon`
                DROP COLUMN `IsArchived`;");
    }
}
