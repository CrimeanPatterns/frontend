<?php

declare(strict_types=1);

namespace AwardWallet\MainBundle\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20190110085456 extends AbstractMigration
{
    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql("
            CREATE TABLE `MerchantReport2` LIKE `MerchantReport`;
            RENAME TABLE `MerchantReport` TO `MerchantReport1`;
            INSERT INTO `Param` (`Name`, `Val`) VALUES ('merchant_report_version', '1');
        ");
    }

    public function down(Schema $schema): void
    {
        $this->addSql("
            DROP TABLE `MerchantReport2`;
            RENAME TABLE `MerchantReport1` TO `MerchantReport`;
            DELETE FROM `Param` WHERE `Name` = 'merchant_report_version';
        ");
    }
}
