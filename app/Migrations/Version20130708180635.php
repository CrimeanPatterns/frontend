<?php

namespace AwardWallet\MainBundle\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your need!
 */
class Version20130708180635 extends AbstractMigration
{
    public function up(Schema $schema): void
    {
        $this->addSql('
			create table IncomeTransaction(
				IncomeTransactionID int not null auto_increment,
				Date datetime not null,
				Processed tinyint default 0,
				primary key(IncomeTransactionID),
				KEY Processed (Processed)
			)
		');
        $this->addSql('ALTER TABLE Cart ADD IncomeTransactionID INT NULL DEFAULT NULL');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('alter table Cart drop column IncomeTransactionID');
        $this->addSql('drop table IncomeTransaction');
    }
}
