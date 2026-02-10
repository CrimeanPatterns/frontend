<?php

namespace AwardWallet\MainBundle\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
class Version20170526034825 extends AbstractMigration
{
    public function up(Schema $schema): void
    {
        $this->addSql("ALTER TABLE AdStat ADD Sent INT DEFAULT '0' NOT NULL COMMENT 'Кол-во запросов рекламы' AFTER Clicks");
    }

    public function down(Schema $schema): void
    {
        $this->addSql("ALTER TABLE AdStat DROP COLUMN Sent");
    }
}
