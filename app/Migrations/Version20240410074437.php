<?php

declare(strict_types=1);

namespace AwardWallet\MainBundle\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20240410074437 extends AbstractMigration
{
    public function up(Schema $schema): void
    {
        $this->addSql("
            ALTER TABLE RAHotelSearchQuery
                DROP INDEX RAHotelSearchQuery_Destination,
                CHANGE Destination Destination VARCHAR(250) DEFAULT NULL COMMENT 'Место назначения',
                ADD DestinationFormatted VARCHAR(250) DEFAULT NULL COMMENT 'Место назначения (форматированное)' AFTER Destination,
                ADD INDEX RAHotelSearchQuery_DestinationFormatted (DestinationFormatted)
        ");
    }

    public function down(Schema $schema): void
    {
        $this->addSql("
            ALTER TABLE RAHotelSearchQuery
                DROP INDEX RAHotelSearchQuery_DestinationFormatted,
                CHANGE Destination Destination VARCHAR(250) NOT NULL COMMENT 'Место назначения',
                DROP DestinationFormatted,
                ADD INDEX RAHotelSearchQuery_Destination (Destination)
        ");
    }
}
