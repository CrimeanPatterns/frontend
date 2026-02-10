<?php

declare(strict_types=1);

namespace AwardWallet\MainBundle\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20210630105801 extends AbstractMigration
{
    public function up(Schema $schema): void
    {
        $this->addSql("
            UPDATE 
                Parking 
            SET GeoTagID = NULL 
            WHERE 
                GeoTagID IS NOT NULL
                AND GeoTagID NOT IN (SELECT GeoTagID FROM GeoTag);
                
            DELETE FROM
                Parking
            WHERE 
                AccountID IS NOT NULL
                AND AccountID NOT IN (SELECT AccountID FROM Account);
                
            DELETE FROM
                Parking
            WHERE 
                UserID IS NOT NULL
                AND UserID NOT IN (SELECT UserID FROM Usr);
                
            DELETE FROM
                Parking
            WHERE 
                UserAgentID IS NOT NULL
                AND UserAgentID NOT IN (SELECT UserAgentID FROM UserAgent);
                
            UPDATE 
                Parking 
            SET TravelPlanID = NULL 
            WHERE 
                TravelPlanID IS NOT NULL
                AND TravelPlanID NOT IN (SELECT TravelPlanID FROM TravelPlan);
                
            UPDATE 
                Parking 
            SET TravelAgencyID = NULL 
            WHERE 
                TravelAgencyID IS NOT NULL
                AND TravelAgencyID NOT IN (SELECT ProviderID FROM Provider);
        ");
        $this->addSql("
            ALTER TABLE Parking 
                ADD UNIQUE KEY Locator(AccountID, Number, UserID, UserAgentID),
                ADD CONSTRAINT Account_fk FOREIGN KEY (AccountID) REFERENCES Account(AccountID) ON UPDATE CASCADE ON DELETE CASCADE,
                ADD CONSTRAINT User_fk FOREIGN KEY (UserID) REFERENCES Usr(UserID) ON DELETE CASCADE,
                ADD CONSTRAINT UserAgent_fk FOREIGN KEY (UserAgentID) REFERENCES UserAgent(UserAgentID) ON DELETE CASCADE,
                ADD CONSTRAINT GeoTag_fk FOREIGN KEY (GeoTagID) REFERENCES GeoTag(GeoTagID) ON DELETE SET NULL,
                ADD CONSTRAINT TravelPlan_fk FOREIGN KEY (TravelPlanID) REFERENCES TravelPlan(TravelPlanID) ON DELETE SET NULL,
                ADD CONSTRAINT TravelAgency_fk FOREIGN KEY (TravelAgencyID) REFERENCES Provider(ProviderID),
                ADD KEY idx_EndDatetime (EndDatetime),
                ADD KEY idx_Hash (AccountID, Hash),
                ADD KEY idx_ProviderID (ProviderID),
                ADD KEY idx_UserHash (UserID, Hash)
        ");
    }

    public function down(Schema $schema): void
    {
        $this->addSql("
            ALTER TABLE Parking 
                DROP KEY idx_UserHash,
                DROP KEY idx_ProviderID,
                DROP KEY idx_Hash,
                DROP KEY idx_EndDatetime,
                DROP FOREIGN KEY TravelAgency_fk,
                DROP FOREIGN KEY TravelPlan_fk,
                DROP FOREIGN KEY GeoTag_fk,
                DROP FOREIGN KEY UserAgent_fk,
                DROP FOREIGN KEY User_fk,
                DROP FOREIGN KEY Account_fk,
                DROP KEY Locator
        ");
    }
}
