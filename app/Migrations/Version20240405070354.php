<?php

declare(strict_types=1);

namespace AwardWallet\MainBundle\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20240405070354 extends AbstractMigration
{
    public function up(Schema $schema): void
    {
        $this->addSql('
            ALTER TABLE RAHotelSearchResult
                ADD INDEX RAHotelSearchResult_Parser (Parser),
                ADD INDEX RAHotelSearchResult_HotelName (HotelName),
                ADD INDEX RAHotelSearchResult_CheckInDate (CheckInDate),
                ADD INDEX RAHotelSearchResult_CheckOutDate (CheckOutDate)
        ');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('
            ALTER TABLE RAHotelSearchResult
                DROP INDEX RAHotelSearchResult_Parser,
                DROP INDEX RAHotelSearchResult_HotelName,
                DROP INDEX RAHotelSearchResult_CheckInDate,
                DROP INDEX RAHotelSearchResult_CheckOutDate
        ');
    }
}
