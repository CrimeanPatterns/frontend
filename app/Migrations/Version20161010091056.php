<?php

namespace AwardWallet\MainBundle\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
class Version20161010091056 extends AbstractMigration
{
    public function up(Schema $schema): void
    {
        // refs #13750
        $this->addSql("
        INSERT ignore INTO TimelineShare (FamilyMemberID, RecipientUserID, TimelineOwnerID, UserAgentID)
          SELECT null, ua.AgentID, ua.ClientID, ua.UserAgentID FROM UserAgent ua
            LEFT JOIN TimelineShare ts on
                 ua.AgentID = ts.RecipientUserID AND 
                 ua.ClientID = ts.TimelineOwnerID AND
                 ua.UserAgentID = ts.UserAgentID AND 
                 ts.FamilyMemberID IS NULL
            WHERE 
              ua.ClientID NOT IN (SELECT UserId FROM Usr where AccountLevel = 3) AND
              ua.ClientID IS NOT NULL AND
              ua.TripAccessLevel = 1 AND
              ts.TimelineShareID IS NULL
            ");
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
    }
}
