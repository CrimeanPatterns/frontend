<?php

declare(strict_types=1);

namespace AwardWallet\MainBundle\Migrations;

use AwardWallet\MainBundle\Entity\Itinerary;
use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20221111111111 extends AbstractMigration
{
    public function up(Schema $schema): void
    {
        $this->addSql("
            CREATE TABLE `ItineraryFile` (
              `ItineraryFileID` int NOT NULL AUTO_INCREMENT,
              `ItineraryTable` tinyint NOT NULL COMMENT 'Какой таблице резервации принадлежит файл Itinerary::ITINERARY_KIND_TABLE',
              `ItineraryID` int NOT NULL COMMENT 'Primary ID записи к которой принадлежит файл',
              `FileName` varchar(200) NOT NULL COMMENT 'Имя файла(которое доступно в т.ч. клиенту)',
              `FileSize` int NOT NULL COMMENT 'Размер файла в байтах',
              `Format` varchar(64) NOT NULL COMMENT 'Тип файла',
              `Description` varchar(250) DEFAULT NULL COMMENT 'Описание файла',
              `StorageKey` varchar(128) NOT NULL COMMENT 'Ключ в хранилище',
              `UploadDate` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP COMMENT 'Время добавления',
              `UUID` char(36) NOT NULL DEFAULT '' COMMENT 'Уникальный идентификатор для доступа к файлу',
              PRIMARY KEY (`ItineraryFileID`),
              UNIQUE KEY `StorageKey` (`StorageKey`),
              KEY `ItinerarySource` (`ItineraryTable`,`ItineraryID`) USING BTREE
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3
        ");

        foreach (Itinerary::$table as $table) {
            $idField = $table . 'ID';
            $notesRows = $this->connection->fetchAllAssociative('
                SELECT ' . $idField . ', Notes
                FROM ' . $table . '
                WHERE
                        Notes REGEXP "\n"
                    AND YEAR(CreateDate) >= 2016
            ');
            foreach ($notesRows as $row) {
                $notes = preg_replace("/(\r?\n){2,}/", "\n\n", $row['Notes']);
                $notes = nl2br(trim($notes));
                if (mb_strlen($notes) >= 4000) {
                    continue;
                }

                $this->connection->update(
                    $table,
                    ['Notes' => $notes],
                    [$idField => $row[$idField]]
                );
            }
        }
    }

    public function down(Schema $schema): void
    {
        $this->addSql('DROP TABLE ItineraryFile');
    }
}
