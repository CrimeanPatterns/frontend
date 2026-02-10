<?php

namespace AwardWallet\MainBundle\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
class Version20170914185115 extends AbstractMigration
{
    public function up(Schema $schema): void
    {
        $this->addSql("
            ALTER TABLE `AccountHistory` 
              ADD `MerchantID` INT  NULL  DEFAULT NULL  AFTER `Category`,
              ADD `Multiplier` DECIMAL(3,1)  NULL  DEFAULT NULL  AFTER `MerchantID`,
              ADD FOREIGN KEY (`MerchantID`) REFERENCES `Merchant` (`MerchantID`) ON DELETE SET NULL,
              ADD INDEX (`MerchantID`, `Multiplier`);
        ");
    }

    public function down(Schema $schema): void
    {
        $this->addSql("
            ALTER TABLE `AccountHistory` 
              DROP FOREIGN KEY `FK_Merchant`,
              DROP `MerchantID`,
              DROP `Multiplier`;
        ");
    }
}
