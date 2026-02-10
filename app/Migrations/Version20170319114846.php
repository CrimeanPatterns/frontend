<?php

namespace AwardWallet\MainBundle\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
class Version20170319114846 extends AbstractMigration
{
    public function up(Schema $schema): void
    {
        $this->addSql("
            ALTER TABLE Usr 
              CHANGE EmailConnected EmailFamilyMemberAlert TINYINT(1) unsigned NOT NULL DEFAULT '1' COMMENT 'Отправлять ли письма, адресованные членам семьи'  AFTER EmailConnectedAlert,
              CHANGE WpNotConnectedAlert WpFamilyMemberAlert TINYINT(1) unsigned NOT NULL DEFAULT '1' COMMENT 'Разрешить webpush-уведомления, предназначенные членам семьи'  AFTER WpConnectedAlert,
              CHANGE MpNotConnectedAlert MpFamilyMemberAlert TINYINT(1) unsigned NOT NULL DEFAULT '1' COMMENT 'Разрешить mobile push-уведомления, предназначенные членам семьи'  AFTER MpConnectedAlert
        ");
    }

    public function down(Schema $schema): void
    {
        $this->addSql("
            ALTER TABLE Usr 
              CHANGE MpFamilyMemberAlert MpNotConnectedAlert TINYINT(1) unsigned NOT NULL DEFAULT '1' COMMENT 'Разрешить mobile push-уведомления, предназначенные членам семьи'  AFTER MpConnectedAlert,
              CHANGE WpFamilyMemberAlert WpNotConnectedAlert TINYINT(1) unsigned NOT NULL DEFAULT '1' COMMENT 'Разрешить webpush-уведомления, предназначенные членам семьи'  AFTER WpConnectedAlert,
              CHANGE EmailFamilyMemberAlert EmailConnected TINYINT(1) unsigned NOT NULL DEFAULT '1' COMMENT 'Отправлять ли письма, адресованные членам семьи'  AFTER EmailConnectedAlert
        ");
    }
}
