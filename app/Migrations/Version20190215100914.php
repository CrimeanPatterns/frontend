<?php

declare(strict_types=1);

namespace AwardWallet\MainBundle\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20190215100914 extends AbstractMigration
{
    public function up(Schema $schema): void
    {
        $this->addSql("UPDATE `Param` SET `Val`='2' WHERE `Name`='merchant_report_version';");

        $tms = time();
        $this->addSql("RENAME TABLE `MerchantReport1` TO `MerchantReport`;");
        $this->addSql("UPDATE `MerchantReport` SET `Tms`={$tms};");
        $this->addSql("
            ALTER TABLE `MerchantReport` 
                DROP INDEX `MerchantReport_ibfk_3`,
                ADD COLUMN `ExpectedMultiplierTransactions` bigint(20) NOT NULL DEFAULT '0' COMMENT 'Кол-во транзакций в таблице AccountHistory c ожидаемым мультипликатором по данной CreditCardID',
                CHANGE `Tms` `Version` INT(11)  NULL  DEFAULT NULL  COMMENT 'Версия (timestamp построения отчета)',
                DROP PRIMARY KEY,
                ADD PRIMARY KEY (`MerchantID`,`CreditCardID`,`ShoppingCategoryID`,`Version`),
                ADD INDEX `MerchantReport_ibfk_1` (`MerchantID`),
                ADD INDEX (`ShoppingCategoryID`),
                ADD INDEX (`Version`),
                ADD INDEX `MerchantReportSelectIndex` (`Version`, `MerchantID`);
        ");
        $this->addSql("UPDATE `Param` SET `Val`={$tms} WHERE Name='merchant_report_version'");
        $this->addSql("DROP TABLE MerchantReport2");
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
    }
}
