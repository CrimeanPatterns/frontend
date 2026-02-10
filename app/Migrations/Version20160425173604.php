<?php

namespace AwardWallet\MainBundle\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

class Version20160425173604 extends AbstractMigration
{
    public function up(Schema $schema): void
    {
        $this->addSql("
            INSERT ignore into TimelineShare (FamilyMemberID, RecipientUserID, TimelineOwnerID, UserAgentID)
            select null, ua.AgentID, ua.ClientID, ua.UserAgentID from UserAgent ua
            LEFT JOIN TimelineShare ts on ua.AgentID = ts.RecipientUserID AND ua.ClientID = ts.TimelineOwnerID and ua.UserAgentID = ts.UserAgentID and ts.FamilyMemberID is null
            where (ua.Source = 'T' or ua.Source = '*') and ua.ClientID is not null and ts.TimelineShareID is null;
        ");
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
    }
}
