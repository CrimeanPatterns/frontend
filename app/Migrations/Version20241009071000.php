<?php

declare(strict_types=1);

namespace AwardWallet\MainBundle\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20241009071000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        $this->addSql(/** @lang MySQL */ "
            CREATE TABLE `PageVisit` (
                `PageName` VARCHAR(64) NOT NULL COMMENT 'Название страницы',
                `UserID` INT(11) NOT NULL COMMENT 'Идентификатор пользователя',
                `Visits` INT(11) NOT NULL DEFAULT 0 COMMENT 'Количество посещений указанной страницы',
                `Day` DATE NOT NULL COMMENT 'Дата посещения',
                KEY `idx-page-visit-user-id` (`UserID`),
                CONSTRAINT `idx-page-visit-unique` UNIQUE (`PageName`, `UserID`, `Day`),
                CONSTRAINT `fk-page-visit-user-id` FOREIGN KEY (`UserID`) REFERENCES `Usr` (`UserID`) ON DELETE CASCADE
            )
            COMMENT 'Статистика посещений страниц определёнными пользователями';
        ");
    }

    public function down(Schema $schema): void
    {
        $this->addSql(/** @lang MySQL */ "
            DROP TABLE `PageVisit`;
        ");
    }
}
