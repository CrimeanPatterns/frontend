<?php

declare(strict_types=1);

namespace AwardWallet\MainBundle\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20190611045625 extends AbstractMigration
{
    public function up(Schema $schema): void
    {
        $this->addSql("UPDATE Usr SET CreationDateTime = NOW() WHERE CreationDateTime < '0000-01-01 00:00:00'");
        $this->addSql("UPDATE Usr SET DiscountedUpgradeBefore = NULL WHERE DiscountedUpgradeBefore < '0000-01-01 00:00:00'");
        $this->addSql("
            ALTER TABLE Usr 
                MODIFY CreationDateTime DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
                MODIFY EmailBookingMessages TINYINT(1) DEFAULT 1 NOT NULL COMMENT 'Отправлять ли письма о новых сообщениях в букинге (только для админов бизнесов ACCESS_ADMIN, ACCESS_BOOKING_MANAGER, ACCESS_BOOKING_VIEW_ONLY)';
                MODIFY WpBookingMessages TINYINT(1) DEFAULT 1 NOT NULL COMMENT 'Разрешить webpush-уведомления при новых сообщениях в букинг-запросах (только для админов бизнесов ACCESS_ADMIN, ACCESS_BOOKING_MANAGER, ACCESS_BOOKING_VIEW_ONLY)';
                MODIFY MpBookingMessages TINYINT(1) DEFAULT 1 NOT NULL COMMENT 'Разрешить mobile push-уведомления при новых сообщениях в букинг-запросах (только для админов бизнесов ACCESS_ADMIN, ACCESS_BOOKING_MANAGER, ACCESS_BOOKING_VIEW_ONLY)';
        ");
    }

    public function down(Schema $schema): void
    {
        $this->addSql("
            ALTER TABLE Usr 
                MODIFY CreationDateTime DATETIME NOT NULL DEFAULT '0000-00-00 00:00:00',
                MODIFY EmailBookingMessages TINYINT(1) DEFAULT 1 NOT NULL COMMENT 'Отправлять ли письма о новых сообщениях в букинге';
                MODIFY WpBookingMessages TINYINT(1) DEFAULT 1 NOT NULL COMMENT 'Разрешить webpush-уведомления при новых сообщениях в букинг-запросах';
                MODIFY MpBookingMessages TINYINT(1) DEFAULT 1 NOT NULL COMMENT 'Разрешить mobile push-уведомления при новых сообщениях в букинг-запросах';
        ");
    }
}
