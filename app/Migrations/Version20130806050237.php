<?php

namespace AwardWallet\MainBundle\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your need!
 */
class Version20130806050237 extends AbstractMigration
{
    public function up(Schema $schema): void
    {
        $this->addSql("
			CREATE TABLE MediaContact (
				MediaContactID INT NOT NULL AUTO_INCREMENT,
				Name VARCHAR(4000) NOT NULL,
				URL VARCHAR(1000) DEFAULT NULL,
				FirstName VARCHAR(30) DEFAULT NULL,
				LastName VARCHAR(50) DEFAULT NULL,
				Email VARCHAR(80) DEFAULT NULL,
				AltContactMethod VARCHAR(4000) DEFAULT NULL,
				LastContactedBy VARCHAR(250) DEFAULT NULL,
				LastContactDate DATETIME DEFAULT NULL,
				Responses TEXT DEFAULT NULL,
				Comments TEXT DEFAULT NULL,
				NDR TINYINT(1) NOT NULL DEFAULT 0,
				Unsubscribed TINYINT(1) NOT NULL DEFAULT 0,
				PRIMARY KEY (MediaContactID),
				UNIQUE KEY Email (Email),
				KEY LastContactDate (LastContactDate)
			) DEFAULT CHARACTER SET utf8 COLLATE utf8_unicode_ci ENGINE = InnoDB;
		");
    }

    public function down(Schema $schema): void
    {
        $this->addSql("
			DROP TABLE MediaContact;
        ");
    }
}
