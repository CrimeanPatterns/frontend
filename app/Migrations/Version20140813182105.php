<?php

namespace AwardWallet\MainBundle\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

class Version20140813182105 extends AbstractMigration
{
    public function up(Schema $schema): void
    {
        $this->addSql("
			CREATE TABLE AdBooker (
				AdBookerID INT unsigned PRIMARY KEY NOT NULL AUTO_INCREMENT,
				SocialAdID INT unsigned NOT NULL,
				BookerID INT unsigned NOT NULL
			)"
        );
        $this->addSql("CREATE UNIQUE INDEX ak_AdBooker ON AdBooker ( SocialAdID, BookerID )");
        $this->addSql("CREATE INDEX fk_AdBooker ON AdBooker ( BookerID )");
        $this->addSql("ALTER TABLE AbBookerInfo ADD DisableAd tinyint unsigned not null default 0 comment 'Отключение рекламы у пользователей букера'");
    }

    public function down(Schema $schema): void
    {
        $this->addSql("drop table AdBooker");
        $this->addSql("alter table AbBookerInfo drop DisableAd");
    }
}
