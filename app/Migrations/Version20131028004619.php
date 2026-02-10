<?php

namespace AwardWallet\MainBundle\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

class Version20131028004619 extends AbstractMigration
{
    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE `Trip` MODIFY COLUMN `RecordLocator` varchar(100);');
        $this->addSql('ALTER TABLE `Reservation` MODIFY COLUMN `ConfirmationNumber` varchar(100);');
        $this->addSql('ALTER TABLE `Rental` MODIFY COLUMN `Number` varchar(100);');
        $this->addSql('ALTER TABLE `Restaurant` MODIFY COLUMN `ConfNo` varchar(100);');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE `Trip` MODIFY COLUMN `RecordLocator` varchar(20);');
        $this->addSql('ALTER TABLE `Reservation` MODIFY COLUMN `ConfirmationNumber` varchar(20);');
        $this->addSql('ALTER TABLE `Rental` MODIFY COLUMN `Number` varchar(20);');
        $this->addSql('ALTER TABLE `Restaurant` MODIFY COLUMN `ConfNo` varchar(20);');
    }
}
