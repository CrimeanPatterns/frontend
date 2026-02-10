<?php

declare(strict_types=1);

namespace AwardWallet\MainBundle\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20211020101010 extends AbstractMigration
{
    public function up(Schema $schema): void
    {
        $this->addSql("ALTER TABLE `Account` ADD `CurrencyID` INT UNSIGNED NULL DEFAULT NULL COMMENT 'Если выбран пользователем для вручную добавленных аккаунтов, то баланс будет форматирован под эту валюту.'");
        $this->addSql('ALTER TABLE `Account` ADD  CONSTRAINT `Account_CurrencyID_fk` FOREIGN KEY (`CurrencyID`) REFERENCES `Currency`(`CurrencyID`) ON DELETE SET NULL ON UPDATE CASCADE');

        $this->addSql("ALTER TABLE `ProviderCoupon` ADD `CurrencyID` INT UNSIGNED NULL DEFAULT NULL COMMENT 'Если выбран пользователем, то баланс будет форматирован под эту валюту.'");
        $this->addSql('ALTER TABLE `ProviderCoupon` ADD  CONSTRAINT `ProviderCoupon_CurrencyID_fk` FOREIGN KEY (`CurrencyID`) REFERENCES `Currency`(`CurrencyID`) ON DELETE SET NULL ON UPDATE CASCADE');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE `Account` DROP INDEX `Account_CurrencyID_fk`');
        $this->addSql('ALTER TABLE `ProviderCoupon` DROP INDEX `ProviderCoupon_CurrencyID_fk`');

        $this->addSql('ALTER TABLE `Account` DROP `CurrencyID`');
        $this->addSql('ALTER TABLE `ProviderCoupon` DROP `CurrencyID`');
    }
}
