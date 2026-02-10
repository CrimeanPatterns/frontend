<?php

namespace AwardWallet\MainBundle\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your need!
 */
class Version20130527144327 extends AbstractMigration
{
    public function up(Schema $schema): void
    {
        $this->addSql("
			CREATE TABLE `UserEmail` (
			  `UserEmailID` int(11) NOT NULL AUTO_INCREMENT,
			  `UserID` int(11) NOT NULL,
			  `Email` varchar(80) COLLATE utf8_unicode_ci NOT NULL,
			  `Password` varchar(4000) COLLATE utf8_unicode_ci NOT NULL,
			  `Status` tinyint(1) NOT NULL,
			  `Added` datetime NOT NULL,
			  PRIMARY KEY (`UserEmailID`),
			  KEY `UserEmail` (`UserID`,`Email`),
			  KEY `Added` (`Added`),
			  CONSTRAINT `UserEmail_ibfk_1` FOREIGN KEY (`UserID`) REFERENCES `Usr` (`UserID`) ON DELETE CASCADE ON UPDATE CASCADE
			) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;
		");
    }

    public function down(Schema $schema): void
    {
        $this->addSql("
			DROP TABLE `UserEmail`;
		");
    }
}
