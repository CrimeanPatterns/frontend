<?php

declare(strict_types=1);

namespace AwardWallet\MainBundle\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20180802084649 extends AbstractMigration
{
    public function up(Schema $schema): void
    {
        $this->addSql("ALTER TABLE `Account`        ADD `LastStoreLocationUpdateDate` datetime default null comment 'Дата последнего обновления Store Locations'");
        $this->addSql("ALTER TABLE `ProviderCoupon` ADD `LastStoreLocationUpdateDate` datetime default null comment 'Дата последнего обновления Store Locations'");
    }

    public function down(Schema $schema): void
    {
        $this->addSql("alter table `Account`        drop `LastStoreLocationUpdateDate`");
        $this->addSql("alter table `ProviderCoupon` drop `LastStoreLocationUpdateDate`");
    }
}
