<?php

declare(strict_types=1);

namespace AwardWallet\MainBundle\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20240409035859 extends AbstractMigration
{
    public function up(Schema $schema): void
    {
        $this->addSql('
            ALTER TABLE RAHotelSearchQuery
                ADD INDEX RAHotelSearchQuery_Destination (Destination)
        ');
        $this->addSql('
            ALTER TABLE RAHotelSearchResult
                ADD COLUMN NumberOfRooms TINYINT NOT NULL DEFAULT 1 COMMENT \'Количество комнат\' AFTER CheckOutDate,
                ADD COLUMN NumberOfAdults TINYINT NOT NULL DEFAULT 1 COMMENT \'Количество взрослых\' AFTER NumberOfRooms,
                ADD COLUMN NumberOfKids TINYINT NOT NULL DEFAULT 0 COMMENT \'Количество детей\' AFTER NumberOfAdults,
                ADD COLUMN ThumbnailKey VARCHAR(40) NULL COMMENT \'Ключ для получения миниатюры\' AFTER Parser
        ');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('
            ALTER TABLE RAHotelSearchQuery
                DROP INDEX RAHotelSearchQuery_Destination
        ');
        $this->addSql('
            ALTER TABLE RAHotelSearchResult
                DROP COLUMN NumberOfRooms,
                DROP COLUMN NumberOfAdults,
                DROP COLUMN NumberOfKids,
                DROP COLUMN ThumbnailKey
        ');
    }
}
