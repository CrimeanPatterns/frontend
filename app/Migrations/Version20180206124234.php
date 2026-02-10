<?php

namespace AwardWallet\MainBundle\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
class Version20180206124234 extends AbstractMigration
{
    public function up(Schema $schema): void
    {
        $this->addSql('update `Provider` set `MobileAutoLogin` = `iPhoneAutoLogin`');
//        $this->addSql('alter table `Provider` drop `iPhoneAutoLogin`');
    }

    public function down(Schema $schema): void
    {
        $this->addSql("
            alter table `Provider`
                add column `iPhoneAutoLogin` tinyint(1) default 1 not null comment 'Мобильный автологин
	*Value*:
	*Disabled* - в приложении будет \"Go to site\", кинет на LoginUrl из схемы Provider
	*Server* - попытается сделать серверный автологин, надпись \"Go to site\" - т.к. серверный в мобильных очень плохо работает.
	*Mobile extension* - надпись \"Autologin\", автологин через мобильный экстеншн. В случае какой-либо ошибки кинет на LoginUrl
	*Desktop extension* - надпись \"Autologin\", автологин через десктопный экстеншн. В случае ошибки кинет на LoginUrl.' after `BarCode`
	    ");
    }
}
