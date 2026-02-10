<?php

namespace AwardWallet\MainBundle\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
class Version20150331050530 extends AbstractMigration
{
    public function up(Schema $schema): void
    {
        $this->addSql("ALTER TABLE `AbBookerInfo` ADD `MerchantName` VARCHAR(20)  NULL  DEFAULT NULL  COMMENT 'Имя для PayPal. Отображается в счете.'  AFTER `ServiceShortName`");
        $this->addSql("UPDATE `AbBookerInfo` SET `MerchantName` = 'BOOKYOURAWA' WHERE `AbBookerInfoID` = '10'");
        $this->addSql("UPDATE `AbBookerInfo` SET `MerchantName` = 'AWARDWALLET' WHERE `AbBookerInfoID` = '8'");
    }

    public function down(Schema $schema): void
    {
        $this->addSql("ALTER TABLE `AbBookerInfo` DROP `MerchantName`");
    }
}
