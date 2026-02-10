<?php

namespace AwardWallet\MainBundle\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
class Version20170914182110 extends AbstractMigration
{
    public function up(Schema $schema): void
    {
        $this->addSql("
            ALTER TABLE `SubAccount` 
              ADD `CreditCardID` INT NULL DEFAULT NULL AFTER `Kind`,
              ADD FOREIGN KEY (`CreditCardID`) REFERENCES `CreditCard` (`CreditCardID`) ON DELETE SET NULL
        ");
    }

    public function down(Schema $schema): void
    {
        $this->addSql("
            ALTER TABLE `SubAccount` 
              DROP FOREIGN KEY `SubAccount_ibfk_2`,
              DROP `CreditCardID`;
        ");
    }
}
