<?php

namespace AwardWallet\MainBundle\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
class Version20170925090504 extends AbstractMigration
{
    public function up(Schema $schema): void
    {
        $this->addSql("
          ALTER TABLE Location DROP FOREIGN KEY Location_Usr_UserID, DROP COLUMN UserID;
        ");
    }

    public function down(Schema $schema): void
    {
        $this->addSql("
          ALTER TABLE Location ADD COLUMN UserID INT DEFAULT NULL COMMENT 'Пользователь, которому принадлежит область для мониторинга' AFTER LocationID,
            ADD CONSTRAINT `Location_Usr_UserID` FOREIGN KEY (`UserID`) REFERENCES `Usr` (`UserID`) ON DELETE CASCADE;
        ");
    }
}
