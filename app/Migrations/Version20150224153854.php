<?php

namespace AwardWallet\MainBundle\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
class Version20150224153854 extends AbstractMigration
{
    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE ContactUs MODIFY Replied TINYINT(4) DEFAULT 0 COMMENT "Был ли дан ответ на запрос";');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE ContactUs MODIFY Replied TINYINT(4) DEFAULT NULL COMMENT "Был ли дан ответ на запрос";');
    }
}
