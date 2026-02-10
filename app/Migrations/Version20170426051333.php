<?php

namespace AwardWallet\MainBundle\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
class Version20170426051333 extends AbstractMigration
{
    public function up(Schema $schema): void
    {
        $this->addSql("ALTER TABLE NotificationTemplate ADD Type TINYINT UNSIGNED NOT NULL DEFAULT 10 COMMENT 'Тип нотификации: offer, product update or new blog posts; \AwardWallet\MainBundle\Service\Notification\Content::TYPE_*' AFTER Message");
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE NotificationTemplate DROP COLUMN Type');
    }
}
