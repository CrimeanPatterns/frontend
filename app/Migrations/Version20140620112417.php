<?php

namespace AwardWallet\MainBundle\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
class Version20140620112417 extends AbstractMigration
{
    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('
            CREATE TABLE `RememberMeToken` (
                `UserID` INT(11) NOT NULL,
                `Series` VARCHAR(88) NOT NULL,
                `Token` VARCHAR(88) NOT NULL,
                `LastUsed` DATETIME NOT NULL,
                KEY (`Series`),
                KEY (`LastUsed`),
                UNIQUE KEY `uniq_token` (`Series`, `Token`),
                CONSTRAINT `RememberMeToken_ibfk_1` FOREIGN KEY (`UserID`) REFERENCES `Usr` (`UserID`) ON DELETE CASCADE ON UPDATE CASCADE
            ) ENGINE=InnoDB
        ');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('drop table RememberMeToken');
    }
}
