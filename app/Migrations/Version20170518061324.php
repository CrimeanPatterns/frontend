<?php

namespace AwardWallet\MainBundle\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
class Version20170518061324 extends AbstractMigration
{
    public function up(Schema $schema): void
    {
        $this->addSql("
            ALTER TABLE SocialAd 
              DROP COLUMN NewDesignMode,
              DROP COLUMN USInternational,
              DROP COLUMN SiteMode,
              DROP FOREIGN KEY SocialAd_ibfk_1,
              DROP COLUMN StateID,
              DROP COLUMN City,
              DROP COLUMN PictureVer,
              DROP COLUMN PictureExt,
              DROP COLUMN Highlights,
              DROP COLUMN RedirectURL,
              DROP COLUMN RedirectID
        ");

        if ($schema->hasTable('Ads')) {
            $schema->dropTable('Ads');
        }
    }

    public function down(Schema $schema): void
    {
        $this->addSql("
            ALTER TABLE SocialAd 
              ADD NewDesignMode tinyint not null DEFAULT 0 COMMENT 'Реклама отображается в новом дизайне' AFTER InternalNote,
              ADD USInternational tinyint(3) unsigned default '0' not null AFTER AllProviders,
              ADD SiteMode tinyint null AFTER ProviderKind,
              ADD StateID int null AFTER SiteMode,
              ADD City varchar(80) null AFTER StateID,
              ADD PictureVer int null AFTER City,
              ADD PictureExt varchar(5) null AFTER PictureVer,
              ADD Highlights text null AFTER PictureExt,
              ADD RedirectURL varchar(250) null AFTER Highlights,
              ADD RedirectID int null AFTER RedirectURL,
              ADD CONSTRAINT SocialAd_ibfk_1 FOREIGN KEY (StateID) REFERENCES State (StateID) ON DELETE CASCADE
        ");
    }
}
