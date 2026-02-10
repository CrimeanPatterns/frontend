<?php

declare(strict_types=1);

namespace AwardWallet\MainBundle\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20240721130251 extends AbstractMigration
{
    public function up(Schema $schema): void
    {
        $this->addSql("
            ALTER TABLE RAFlightSearchQuery
            MODIFY COLUMN UserID INT NULL COMMENT 'Кто создал запрос (в ручном режиме). В автоматическом режиме - NULL, т.к. на юзера указывает Trip.UserID',
            ADD COLUMN Subscribers JSON NULL COMMENT 'Список подписчиков на результаты поиска' AFTER MileValueID
        ");
    }

    public function down(Schema $schema): void
    {
        $this->addSql("
            ALTER TABLE RAFlightSearchQuery
            MODIFY COLUMN UserID INT NOT NULL COMMENT 'Кто создал запрос',
            DROP COLUMN Subscribers
        ");
    }
}
