<?php

declare(strict_types=1);

namespace AwardWallet\MainBundle\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20210211103439 extends AbstractMigration
{
    public function up(Schema $schema): void
    {
        $this->addSql("alter table `AirHelpCompensation` modify column `partner_travel_id` int(11) not null");
        $this->addSql('alter table `AirHelpCompensation` ADD COLUMN UserID INT DEFAULT NULL');
        $this->addSql('alter table `AirHelpCompensation` ADD COLUMN UserAgentID INT DEFAULT NULL');
        $this->addSql('alter table `AirHelpCompensation` ADD CONSTRAINT `AirHelpCompensation_Usr_UserID` FOREIGN KEY (`UserID`) REFERENCES `Usr` (`UserID`) ON DELETE CASCADE');
        $this->addSql('alter table `AirHelpCompensation` ADD CONSTRAINT `AirHelpCompensation_Usr_UserAgentID` FOREIGN KEY (`UserAgentID`) REFERENCES `UserAgent` (`UserAgentID`) ON DELETE CASCADE');
        $this->addSql('alter table `AirHelpCompensation` add index `AirHelpCompensation_EpochTripID` (`epoch`, `partner_travel_id`)');

        $this->addSql('
            update AirHelpCompensation ahc
            join Trip t on
                t.TripID = ahc.partner_travel_id
            set
                ahc.UserID = t.UserID,
                ahc.UserAgentID = t.UserAgentID
        ');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('alter table `AirHelpCompensation` drop index `AirHelpCompensation_EpochTripID`');
        $this->addSql('alter table `AirHelpCompensation` drop foreign key `AirHelpCompensation_Usr_UserAgentID`');
        $this->addSql('alter table `AirHelpCompensation` drop foreign key `AirHelpCompensation_Usr_UserID`');
        $this->addSql('alter table `AirHelpCompensation` drop column `UserAgentID`');
        $this->addSql('alter table `AirHelpCompensation` drop column `UserID`');
        $this->addSql('alter table `AirHelpCompensation` modify column `partner_travel_id` varchar(64) default null');
    }
}
