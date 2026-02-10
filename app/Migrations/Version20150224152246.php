<?php

namespace AwardWallet\MainBundle\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

class Version20150224152246 extends AbstractMigration
{
    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE ContactUs ADD Comments TEXT DEFAULT NULL COMMENT "Комментарии к запросу"');
        $this->addSql('ALTER TABLE ContactUs ADD Replied TINYINT(4) DEFAULT NULL COMMENT "Был ли дан ответ на запрос"');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE ContactUs DROP Comments');
        $this->addSql('ALTER TABLE ContactUs DROP Replied');
    }
}
