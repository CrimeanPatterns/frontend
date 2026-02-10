<?php

declare(strict_types=1);

namespace AwardWallet\MainBundle\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20211211134432 extends AbstractMigration
{
    public function up(Schema $schema): void
    {
        $this->addSql("
            TRUNCATE TABLE LoungeSourceChange;
            
            DELETE FROM LoungeSource WHERE SourceCode = 'priorityPass';
            
            ALTER TABLE LoungeSource
                ADD DeleteDate DATETIME NULL COMMENT 'Лаундж не был собран и подлежит удалению через некоторое время' AFTER UpdateDate,
                ADD ParseDate DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP COMMENT 'Дата последнего парсинга' AFTER DeleteDate;
        ");
    }

    public function down(Schema $schema): void
    {
        $this->addSql("
            ALTER TABLE LoungeSource
                DROP DeleteDate,
                DROP ParseDate;
        ");
    }
}
