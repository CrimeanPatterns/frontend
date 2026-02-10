<?php

namespace AwardWallet\MainBundle\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
class Version20161215130716 extends AbstractMigration
{
    public function up(Schema $schema): void
    {
        $this->addSql("ALTER TABLE Provider ADD COLUMN CheckInReminderOffsets VARCHAR(200) NOT NULL DEFAULT '{\"mail\":[24],\"push\":[1,3,24]}' COMMENT 'настройки нотификаций для пушей и имейлов';");
        $this->addSql("UPDATE `Provider` SET CheckInReminderOffsets = '{\"mail\":[23],\"push\":[1,3,23]}' WHERE Code IN ('lufthansa', 'swisscorporate')");
        $this->addSql("UPDATE `Provider` SET CheckInReminderOffsets = '{\"mail\":[24.5],\"push\":[1,3,24.5]}' WHERE Code = 'rapidrewards'");
        $this->addSql("UPDATE `Provider` SET CheckInReminderOffsets = '{\"mail\":[48],\"push\":[1,3,48]}' WHERE Code = 'velocity'");
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE `Provider` DROP COLUMN CheckInReminderOffsets');
    }
}
