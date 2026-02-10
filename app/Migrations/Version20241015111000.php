<?php

declare(strict_types=1);

namespace AwardWallet\MainBundle\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20241015111000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        $this->addSql(/** @lang MySQL */ "
            CREATE TABLE `UserQuestion` (
                `UserID` INT(11) NOT NULL,
                `SortIndex` SMALLINT(1) NOT NULL COMMENT 'Question number in the list',
                `Question` VARCHAR(64) NOT NULL COMMENT 'The field stores the translation key, the values are in UserQuestion::getQuestionsArray()',
                `Answer` VARBINARY(2000) NOT NULL COMMENT 'The field stores the encrypted answer using Encryptor::encrypt()',
                KEY `idx-user-question-user-id` (`UserID`),
                CONSTRAINT `idx-user-question-unique` UNIQUE (`UserID`, `SortIndex`, `Question`),
                CONSTRAINT `fk-user-question-user-id` FOREIGN KEY (`UserID`) REFERENCES `Usr` (`UserID`) ON DELETE CASCADE 
            )
            COMMENT 'Custom login security questions';
        ");
    }

    public function down(Schema $schema): void
    {
        $this->addSql(/** @lang MySQL */ "
            DROP TABLE `UserQuestion`;
        ");
    }
}
