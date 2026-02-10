<?php

declare(strict_types=1);

namespace AwardWallet\MainBundle\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20241021102300 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        $this->addSql(/** @lang MySQL */ "
            ALTER TABLE `PageVisit`
                ADD COLUMN `IsMobile` TINYINT(1) NOT NULL DEFAULT 0 COMMENT 'Флаг, отображающий, что посещение было в мобильном приложении';
            
            ALTER TABLE `PageVisit`
                DROP KEY `idx-page-visit-unique`;

            ALTER TABLE `PageVisit`
                ADD CONSTRAINT `idx-page-visit-unique` UNIQUE (`PageName`, `UserID`, `Day`, `IsMobile`);
        ");
    }

    public function down(Schema $schema): void
    {
        $this->addSql(/** @lang MySQL */ "
            ALTER TABLE `PageVisit`
                DROP KEY `idx-page-visit-unique`;

            ALTER TABLE `PageVisit`
                ADD CONSTRAINT `idx-page-visit-unique` UNIQUE (`PageName`, `UserID`, `Day`);

            ALTER TABLE `PageVisit`
                DROP COLUMN `IsMobile`;
        ");
    }
}
