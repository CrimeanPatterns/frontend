<?php

namespace AwardWallet\MainBundle\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
class Version20170401181759 extends AbstractMigration
{
    public function up(Schema $schema): void
    {
        $this->addSql('alter table `NotificationTemplate` drop column `DisplayDuration`');
        $this->addSql("alter table `NotificationTemplate` add column `AutoClose` int(1) not null default '0' comment 'если платформа поддерживает эту фичу, то: 0 - нотификация пропадет только после действия пользователя, 1 - нотификация пропадет автоматически по решению платформы'");
        $this->addSql("alter table `NotificationTemplate` add column `TTL_tmp` datetime not null default '2017-04-01 00:00:00' comment 'дата, после которой нотификация должна считаться невалидной'");
        $this->addSql('update `NotificationTemplate` set `TTL_tmp` = date_add(UpdateDate, interval TTL second)');
        $this->addSql("alter table `NotificationTemplate` drop column `TTL`");
        $this->addSql("alter table `NotificationTemplate` change column `TTL_tmp` `TTL` datetime not null comment 'дата, после которой нотификация должна считаться невалидной'");
    }

    public function down(Schema $schema): void
    {
        $this->addSql("ALTER TABLE `NotificationTemplate` ADD `DisplayDuration` INT(11)  NOT NULL  DEFAULT '0'  COMMENT 'Продолжительность показа пуша на экране'  AFTER `TTL`");
        $this->addSql('alter table `NotificationTemplate` drop `AutoClose`');
        $this->addSql("alter table `NotificationTemplate` drop column `TTL`");
        $this->addSql("alter table `NotificationTemplate` add column `TTL` int(11) not null default '0'");
        $this->addSql('update `NotificationTemplate` set `TTL` = 24 * 3600');
    }
}
