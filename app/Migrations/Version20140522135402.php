<?php

namespace AwardWallet\MainBundle\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
class Version20140522135402 extends AbstractMigration
{
    public function up(Schema $schema): void
    {
        $this->addSql("REPLACE INTO AbRequestStatus (AbRequestStatusID, BookerID, Status) VALUES (1, 116000, 'Award structure proposed')");
        $this->addSql("REPLACE INTO AbRequestStatus (AbRequestStatusID, BookerID, Status) VALUES (2, 116000, 'Flight details revealed')");
        $this->addSql("REPLACE INTO AbRequestStatus (AbRequestStatusID, BookerID, Status) VALUES (3, 116000, 'Acct info requested')");
        $this->addSql("REPLACE INTO AbRequestStatus (AbRequestStatusID, BookerID, Status) VALUES (5, 116000, 'Acct info received; booking queue')");
        $this->addSql("REPLACE INTO AbRequestStatus (AbRequestStatusID, BookerID, Status) VALUES (6, 116000, 'Award partial booked, addtl search needed')");
        $this->addSql("REPLACE INTO AbRequestStatus (AbRequestStatusID, BookerID, Status) VALUES (7, 116000, 'Client concern/objection')");
        $this->addSql("REPLACE INTO AbRequestStatus (AbRequestStatusID, BookerID, Status) VALUES (8, 116000, 'Client concern/objection responded')");
        $this->addSql("REPLACE INTO AbRequestStatus (AbRequestStatusID, BookerID, Status) VALUES (9, 116000, '24 hr award cancel alert')");
        $this->addSql("REPLACE INTO AbRequestStatus (AbRequestStatusID, BookerID, Status) VALUES (10, 116000, '24 hr award cancelled')");
        $this->addSql("REPLACE INTO AbRequestStatus (AbRequestStatusID, BookerID, Status) VALUES (11, 116000, 'Get courtesy hold')");
        $this->addSql("REPLACE INTO AbRequestStatus (AbRequestStatusID, BookerID, Status) VALUES (12, 116000, 'Courtesy hold booked')");
        $this->addSql("REPLACE INTO AbRequestStatus (AbRequestStatusID, BookerID, Status) VALUES (13, 116000, 'No Response Send Email')");

        $this->addSql("REPLACE INTO AbMessageColor (AbMessageColorID, BookerID, Color, Description) VALUES (1, 116000, 'purple', 'Internal Communication')");
        $this->addSql("REPLACE INTO AbMessageColor (AbMessageColorID, BookerID, Color, Description) VALUES (2, 116000, 'light-green', 'Outbound International')");
        $this->addSql("REPLACE INTO AbMessageColor (AbMessageColorID, BookerID, Color, Description) VALUES (3, 116000, 'regular-green', 'Outbound Domestic')");
        $this->addSql("REPLACE INTO AbMessageColor (AbMessageColorID, BookerID, Color, Description) VALUES (4, 116000, 'darker-green', 'Outbound Intra-continental')");
        $this->addSql("REPLACE INTO AbMessageColor (AbMessageColorID, BookerID, Color, Description) VALUES (5, 116000, 'light-orange', 'Inbound International')");
        $this->addSql("REPLACE INTO AbMessageColor (AbMessageColorID, BookerID, Color, Description) VALUES (6, 116000, 'regular-orange', 'Inbound Intra-continental')");
        $this->addSql("REPLACE INTO AbMessageColor (AbMessageColorID, BookerID, Color, Description) VALUES (7, 116000, 'dark-orange', 'Inbound Domestic')");
        $this->addSql("REPLACE INTO AbMessageColor (AbMessageColorID, BookerID, Color, Description) VALUES (8, 116000, 'red', 'Stopover')");
        $this->addSql("REPLACE INTO AbMessageColor (AbMessageColorID, BookerID, Color, Description) VALUES (9, 116000, 'blue', 'Final Proposal')");

        $this->addSql("update AbMessage set ColorID = 1 where Metadata like '%\"Color\":\"purple\"%'");
        $this->addSql("update AbMessage set ColorID = 2 where Metadata like '%\"Color\":\"light-green\"%'");
        $this->addSql("update AbMessage set ColorID = 3 where Metadata like '%\"Color\":\"regular-green\"%'");
        $this->addSql("update AbMessage set ColorID = 4 where Metadata like '%\"Color\":\"darker-green\"%'");
        $this->addSql("update AbMessage set ColorID = 5 where Metadata like '%\"Color\":\"light-orange\"%'");
        $this->addSql("update AbMessage set ColorID = 6 where Metadata like '%\"Color\":\"regular-orange\"%'");
        $this->addSql("update AbMessage set ColorID = 7 where Metadata like '%\"Color\":\"dark-orange\"%'");
        $this->addSql("update AbMessage set ColorID = 8 where Metadata like '%\"Color\":\"red\"%'");
        $this->addSql("update AbMessage set ColorID = 9 where Metadata like '%\"Color\":\"blue\"%'");
    }

    public function down(Schema $schema): void
    {
    }
}
