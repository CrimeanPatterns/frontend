<?php

namespace AwardWallet\MainBundle\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

class Version20170126120455 extends AbstractMigration
{
    public function up(Schema $schema): void
    {
        $this->addSql("
            create table `NotificationTemplate`(
                `NotificationTemplateID` int not null auto_increment,
                `Title` varchar(100) not null,
                `Message` text,
                `Link` varchar(1000) not null,
                `TTL` int(11) not null default '0',
                `UserGroups` text,
                `DeliveryMode` tinyint(3) not null default '0',
                `State` tinyint(3) not null default '0',
                `CreateDate` datetime not null,
                `UpdateDate` datetime not null,
                `QueueStat` int(11) not null default '0',
                `SendStat` int(11) not null default '0',
                primary key(`NotificationTemplateID`)
            );
        ");
    }

    public function down(Schema $schema): void
    {
        $this->addSql("DROP TABLE `NotificationTemplate`");
    }
}
