<?php declare(strict_types=1);

namespace AwardWallet\MainBundle\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20210608134015 extends AbstractMigration
{
    public function up(Schema $schema) : void
    {
        $this->addSql("
            ALTER TABLE `LoungePage` 
                DROP AirCodeID,
                DROP FOREIGN KEY `AirCodeID_fk`
        ");
    }

    public function down(Schema $schema) : void
    {
        $this->addSql("
            ALTER TABLE `LoungePage`
                ADD AirCodeID INT(11) COMMENT 'Идентификатор IATA-кода аэропорта'
        ");
        $this->addSql("
            ALTER TABLE `LoungePage` 
                ADD CONSTRAINT `AirCodeID_fk` FOREIGN KEY (`AirCodeID`) REFERENCES `AirCode` (`AirCodeID`) ON DELETE CASCADE ON UPDATE CASCADE
        ");
    }
}

