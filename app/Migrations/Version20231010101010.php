<?php

declare(strict_types=1);

namespace AwardWallet\MainBundle\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20231010101010 extends AbstractMigration
{
    public function up(Schema $schema): void
    {
        $this->addSql("DELETE FROM `QsTransaction` WHERE ClickDate > '2023-09-01'");

        $this->addSql("
            ALTER TABLE `QsTransaction`
                ADD `Advertiser` VARCHAR(128) NULL DEFAULT NULL,
                ADD `Impressions` INT NULL DEFAULT NULL,
                ADD `ClickKey` VARCHAR(64) NULL DEFAULT NULL, 
                ADD `MarketplaceConversionId` INT NULL DEFAULT NULL, 
                ADD `DeviceType` VARCHAR(64) NULL DEFAULT NULL, 
                ADD `CreditCardTypeName` VARCHAR(128) NULL DEFAULT NULL 
        ");
    }

    public function down(Schema $schema): void
    {
        $this->addSql('
            ALTER TABLE `QsTransaction`
                DROP `Advertiser`,
                DROP `Impressions`,
                DROP `ClickKey`,
                DROP `MarketplaceConversionId`,
                DROP `DeviceType`,
                DROP `CreditCardTypeName`; 
        ');
    }
}
