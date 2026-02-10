<?php

declare(strict_types=1);

namespace AwardWallet\MainBundle\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20250408091552 extends AbstractMigration
{
    public function up(Schema $schema): void
    {
        $this->addSql("
            CREATE TABLE LoungeKeyValueStorage (
                LoungeKeyValueStorageID VARCHAR(255) NOT NULL COMMENT 'Уникальный идентификатор записи (ключ)',
                `Value` JSON COMMENT 'Значение в формате JSON',
                ExpirationDate DATETIME NULL COMMENT 'Дата, после которой запись считается устаревшей и может быть удалена',
                CreatedAt TIMESTAMP DEFAULT CURRENT_TIMESTAMP COMMENT 'Дата и время создания записи',
                PRIMARY KEY (LoungeKeyValueStorageID),
                INDEX idx_ExpirationDate (ExpirationDate) COMMENT 'Индекс для быстрого поиска и удаления просроченных записей'
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT 'Таблица для долгосрочного хранения данных о лаунжах аэропортов в формате ключ-значение'
        ");
    }

    public function down(Schema $schema): void
    {
        $this->addSql("DROP TABLE IF EXISTS LoungeKeyValueStorage");
    }
}
