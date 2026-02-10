<?php

declare(strict_types=1);

namespace AwardWallet\MainBundle\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20201125104835 extends AbstractMigration
{
    public function up(Schema $schema): void
    {
        $this->addSql("
            ALTER TABLE `Lounge`
                ADD AirCodeID INT(11) NOT NULL COMMENT 'Идентификатор IATA-кода аэропорта'
        ");
        $this->addSql('
            update `Lounge` l
            left join `AirCode` a on l.AirportCode = a.AirCode
            set l.AirCodeID = a.AirCodeID
        ');

        $this->addSql('
            update `Lounge`
            set `UpdateDate` = `CreateDate` 
            where `UpdateDate` is null');

        $this->addSql("
            ALTER TABLE `Lounge` 
                MODIFY COLUMN PageBody MEDIUMTEXT,
                MODIFY COLUMN URL VARCHAR(2000),
                MODIFY COLUMN UpdateDate DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
                ADD CONSTRAINT `AirCodeID_fk` FOREIGN KEY (`AirCodeID`) REFERENCES `AirCode` (`AirCodeID`) ON DELETE CASCADE ON UPDATE CASCADE
        ");
    }

    public function down(Schema $schema): void
    {
        $this->addSql("
            ALTER TABLE `Lounge` 
                DROP AirCodeID,
                DROP FOREIGN KEY `AirCodeID_fk`
        ");
    }
}
