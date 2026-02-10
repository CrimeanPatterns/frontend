<?php

namespace AwardWallet\MainBundle\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
class Version20130911052933 extends AbstractMigration
{
    public function up(Schema $schema): void
    {
        $this->addSql("
        CREATE TABLE UserEmailParseHistory (
			 UserEmailParseHistoryID INT NOT NULL auto_increment,
			 UserEmailID             INT NOT NULL,
			 EmailToken              VARCHAR(32) NOT NULL,
			 EmailDate               DATETIME NOT NULL,
			 ParseDate               DATETIME,
			 PRIMARY KEY (UserEmailParseHistoryID),
			 INDEX idx1_uehistory1_token (EmailToken),
			 INDEX idx2_uehistory1_date (EmailDate),
			 CONSTRAINT fk1_uehistory1_useremail FOREIGN KEY (UserEmailID) REFERENCES
			 UserEmail (UserEmailID) ON DELETE CASCADE ON UPDATE CASCADE
		  )	ENGINE = InnoDB;
		CREATE TABLE UserEmailAccountHistory (
			 UserEmailAccountHistoryID INT NOT NULL auto_increment,
			 UserEmailID               INT NOT NULL,
			 AccountID                 INT NOT NULL,
			 UpdateDate                DATETIME,
			 PRIMARY KEY (UserEmailAccountHistoryID),
			 CONSTRAINT fk1_uehistory2_useremail FOREIGN KEY (UserEmailID) REFERENCES
			 UserEmail (UserEmailID) ON DELETE CASCADE ON UPDATE CASCADE,
			 CONSTRAINT fk2_uehistory2_account FOREIGN KEY (AccountID) REFERENCES
			 Account (AccountID) ON DELETE CASCADE ON UPDATE CASCADE
		  )	ENGINE = InnoDB;
        ");
    }

    public function down(Schema $schema): void
    {
        $this->addSql("
        DROP TABLE UserEmailParseHistory;
        DROP TABLE UserEmailAccountHistory;
        ");
    }
}
