<?php

namespace AwardWallet\MainBundle\Migrations;

use Doctrine\DBAL\Schema\Schema;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
class Version20170221150950 extends Version20170216122245
{
    public function up(Schema $schema): void
    {
        $this->addSql("ALTER TABLE Provider MODIFY COLUMN CheckInReminderOffsets VARCHAR(200) NOT NULL DEFAULT '{\"mail\":[24],\"push\":[1,4,24]}' COMMENT 'настройки нотификаций для пушей и имейлов';");

        parent::up($schema);
    }

    public function down(Schema $schema): void
    {
        parent::down($schema);
    }
}
