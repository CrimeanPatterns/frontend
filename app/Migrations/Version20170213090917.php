<?php

namespace AwardWallet\MainBundle\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
class Version20170213090917 extends AbstractMigration
{
    public function up(Schema $schema): void
    {
        $this->addSql("ALTER TABLE CardImage ADD COLUMN SubAccountID INT(11) DEFAULT NULL COMMENT 'субаккаут, к которому привязана картинка' AFTER AccountID;");
        $this->addSql('ALTER TABLE CardImage ADD CONSTRAINT `idx_CardImage_SubAccount` UNIQUE KEY (`SubAccountID`, `Kind`)');
        $this->addSql('ALTER TABLE CardImage ADD CONSTRAINT `fk_CardImage_Account_SubAccountID` FOREIGN KEY (SubAccountID) REFERENCES SubAccount(SubAccountID) ON DELETE SET NULL');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE CardImage DROP FOREIGN KEY `fk_CardImage_Account_SubAccountID`');
        $this->addSql('ALTER TABLE CardImage DROP INDEX `idx_CardImage_SubAccount`');
        $this->addSql('ALTER TABLE CardImage DROP SubAccountID');
    }
}
