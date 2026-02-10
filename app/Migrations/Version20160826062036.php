<?php

namespace AwardWallet\MainBundle\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
class Version20160826062036 extends AbstractMigration
{
    public function up(Schema $schema): void
    {
        $this->addSql("
			CREATE TABLE MegaDO7Promo(
			    UserID INT(11) NOT NULL,	
				ViewDate DATETIME NOT NULL COMMENT 'Дата просмотра оффера пользователем',
				IsAgreed int(1) DEFAULT 0 NOT NULL COMMENT 'Участвует ли пользователь в розыгрыше',
				UNIQUE KEY UserID (UserID),
				CONSTRAINT `MegaDO7Log_ibfk_0` FOREIGN KEY (`UserID`) REFERENCES `Usr` (`UserID`) ON DELETE CASCADE
			)
		");
    }

    public function down(Schema $schema): void
    {
        $this->addSql('DROP TABLE `MegaDO7Promo`');
    }
}
