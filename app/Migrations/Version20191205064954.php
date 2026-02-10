<?php

declare(strict_types=1);

namespace AwardWallet\MainBundle\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20191205064954 extends AbstractMigration
{
    public function up(Schema $schema): void
    {
        $this->addSql("
            ALTER TABLE `Usr` 
            ADD `AT201ExpirationDate` DATE  NULL  COMMENT 'Дата протухания подписки на at201'  AFTER `PlusExpirationDate`,
            ADD `SubscriptionType` TINYINT  NULL  COMMENT 'Тип подписки: 1 - AwPlus, 2 - AT201'  AFTER `Subscription`;
        ");

        $this->addSql("UPDATE `Usr` SET `SubscriptionType` = 1 WHERE `Subscription` IS NOT NULL");
    }

    public function down(Schema $schema): void
    {
        $this->addSql("
            ALTER TABLE `Usr` 
            DROP `AT201ExpirationDate`,
            DROP `SubscriptionType`;
        ");
    }
}
