<?php

declare(strict_types=1);

namespace AwardWallet\MainBundle\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20240405065203 extends AbstractMigration
{
    public function up(Schema $schema): void
    {
        $this->addSql("
            ALTER TABLE RAHotelSearchQuery
                ADD COLUMN ChannelName VARCHAR(255) NOT NULL COMMENT 'Название канала для связи с клиентом';
        ");
    }

    public function down(Schema $schema): void
    {
        $this->addSql('
            ALTER TABLE RAHotelSearchQuery
                DROP COLUMN ChannelName;
        ');
    }
}
