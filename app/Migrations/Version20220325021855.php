<?php

declare(strict_types=1);

namespace AwardWallet\MainBundle\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20220325021855 extends AbstractMigration
{
    public function up(Schema $schema): void
    {
        $this->addSql("
            CREATE TABLE PlanFile (
                PlanFileID INT(11) NOT NULL AUTO_INCREMENT,
                PlanID INT(11) NOT NULL COMMENT 'Травел план',
                FileName VARCHAR(200) NOT NULL COMMENT 'Имя файла(которое доступно в т.ч. клиенту)',
                FileSize INT(11) NOT NULL COMMENT 'Размер файла в байтах',
                Format VARCHAR(64) NOT NULL COMMENT 'Тип файла',
                Description VARCHAR(250) NULL COMMENT 'Описание файла',
                StorageKey VARCHAR(128) NOT NULL COMMENT 'Ключ в хранилище',
                UploadDate DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP COMMENT 'Время добавления',
                UUID CHAR(36) NOT NULL DEFAULT '' COMMENT 'Уникальный идентификатор для доступа к файлу',
                
                PRIMARY KEY (PlanFileID),
                UNIQUE KEY (StorageKey),
                
                CONSTRAINT PlanFile_Plan_PlanID FOREIGN KEY (PlanID) REFERENCES Plan (PlanID) ON DELETE CASCADE
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8
        ");
    }

    public function down(Schema $schema): void
    {
        $this->addSql('DROP TABLE PlanFile');
    }
}
