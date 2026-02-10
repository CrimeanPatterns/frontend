<?php

namespace AwardWallet\MainBundle\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your need!
 */
class Version20130605143816 extends AbstractMigration
{
    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql("
			ALTER TABLE `BookingHistory` CHANGE `Data` `Data` VARCHAR(4000)  NULL  DEFAULT NULL;
		");
        $this->addSql("
			ALTER TABLE `BookingInvoice` ADD `PaymentType` TINYINT(1)  NOT NULL  DEFAULT '0'  AFTER `Status`;
		");
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql("
			ALTER TABLE `BookingHistory` CHANGE `Data` `Data` VARCHAR(250)  NULL  DEFAULT NULL;
		");
        $this->addSql("
			ALTER TABLE `BookingInvoice` DROP `PaymentType`;
		");
    }
}
