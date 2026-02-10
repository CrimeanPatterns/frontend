<?php

declare(strict_types=1);

namespace AwardWallet\MainBundle\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20180813103824 extends AbstractMigration
{
    public function up(Schema $schema): void
    {
        $this->addSql("
          ALTER TABLE `AbRequest` 
            ADD `InboundAirlineID` INT  NULL  DEFAULT NULL,
            ADD `OutboundAirlineID` INT  NULL  DEFAULT NULL,
            ADD CONSTRAINT `fk_InboundAirlineID` FOREIGN KEY (`InboundAirlineID`) REFERENCES `Airline` (`AirlineID`) ON DELETE SET NULL,
            ADD CONSTRAINT `fk_OutboundAirlineID` FOREIGN KEY (`OutboundAirlineID`) REFERENCES `Airline` (`AirlineID`) ON DELETE SET NULL;
        ");
    }

    public function down(Schema $schema): void
    {
        $this->addSql("
          ALTER TABLE `AbRequest` 
            DROP FOREIGN KEY `fk_InboundAirlineID`,
            DROP FOREIGN KEY `fk_OutboundAirlineID`,            
            DROP `InboundAirlineID`,
            DROP `OutboundAirlineID`;
        ");
    }
}
