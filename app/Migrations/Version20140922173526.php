<?php

namespace AwardWallet\MainBundle\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

class Version20140922173526 extends AbstractMigration
{
    public function up(Schema $schema): void
    {
        $this->addSql("drop table EmailAccount");
    }

    public function down(Schema $schema): void
    {
        $this->addSql("CREATE TABLE `EmailAccount` (
          `EmailAccountID` int(11) NOT NULL AUTO_INCREMENT,
          `Name` varchar(80) NOT NULL,
          `Domain` varchar(80) NOT NULL,
          `Password` varchar(80) NOT NULL,
          `IsCorrect` int(11) DEFAULT NULL,
          `LastUseDate` datetime DEFAULT NULL,
          `Invited` int(11) DEFAULT NULL,
          `FriendsFound` int(11) DEFAULT NULL,
          PRIMARY KEY (`EmailAccountID`),
          UNIQUE KEY `Name` (`Name`,`Domain`)
        ) ENGINE=InnoDB");
    }
}
