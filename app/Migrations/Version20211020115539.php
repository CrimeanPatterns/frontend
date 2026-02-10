<?php

declare(strict_types=1);

namespace AwardWallet\MainBundle\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20211020115539 extends AbstractMigration
{
    public function up(Schema $schema): void
    {
        $this->addSql("
            ALTER TABLE LoungePage
                DROP COLUMN URL,
                DROP COLUMN IsFullURL,
                ADD Assets JSON NOT NULL DEFAULT (JSON_ARRAY()) COMMENT 'Ссылки на веб-сайт, изображения' AFTER SourceID
        ");
    }

    public function down(Schema $schema): void
    {
        $this->addSql("
            ALTER TABLE LoungePage
                ADD URL VARCHAR(2000) NULL COMMENT 'URL' AFTER SourceID,
                ADD IsFullURL TINYINT DEFAULT 1 NOT NULL COMMENT '1 - URL полный (указывает напрямую на страницу зала), 0 - частичный (может указывать на все или группу залов)' AFTER URL,
                DROP COLUMN Assets;
        ");
    }
}
