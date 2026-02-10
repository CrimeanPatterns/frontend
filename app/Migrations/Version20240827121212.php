<?php

declare(strict_types=1);

namespace AwardWallet\MainBundle\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20240827121212 extends AbstractMigration
{
    public function up(Schema $schema): void
    {
        $this->addSql('
            CREATE TABLE `CreditCardOffer` (
              `CreditCardOfferID` int NOT NULL AUTO_INCREMENT,
              `StartDate` datetime NOT NULL,
              `EndDate` datetime DEFAULT NULL,
              `CreditCardID` int NOT NULL,
              `OfferNote` text NOT NULL,
              `SubjectiveValue` int DEFAULT NULL,
              PRIMARY KEY (`CreditCardOfferID`),
              KEY `CreditCardOffer_CreditCardID` (`CreditCardID`),
              CONSTRAINT `CreditCardOffer_CreditCardID` FOREIGN KEY (`CreditCardID`) REFERENCES `CreditCard` (`CreditCardID`) ON DELETE CASCADE ON UPDATE CASCADE
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3
        ');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('DROP TABLE `CreditCardOffer`');
    }
}
