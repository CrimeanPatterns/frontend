<?php

namespace AwardWallet\MainBundle\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
class Version20140609165404 extends AbstractMigration
{
    public function up(Schema $schema): void
    {
        $this->addSql("UPDATE AbRequest SET InternalStatus = null");
        $this->addSql("DELETE FROM AbRequestStatus WHERE BookerID = 116000");
        $this->addSql("ALTER TABLE AbRequestStatus ADD COLUMN `Order` smallint NOT NULL AFTER Status");
        $this->addSql("ALTER TABLE AbRequestStatus ADD COLUMN `Level` varchar(255) AFTER `Order`");

        $this->addSql("INSERT INTO AbRequestStatus (BookerID, Status, `Order`, Level) VALUES (116000, 'Spam Filter Warning', 10, 'Level 1')");
        $this->addSql("INSERT INTO AbRequestStatus (BookerID, Status, `Order`, Level) VALUES (116000, 'Client Vetting Received', 20, 'Level 1')");
        $this->addSql("INSERT INTO AbRequestStatus (BookerID, Status, `Order`, Level) VALUES (116000, 'Client Vetting/No Response #1', 30, 'Level 1')");
        $this->addSql("INSERT INTO AbRequestStatus (BookerID, Status, `Order`, Level) VALUES (116000, 'Client Vetting/No Response #2-Cancel', 40, 'Level 1')");

        $this->addSql("INSERT INTO AbRequestStatus (BookerID, Status, `Order`, Level) VALUES (116000, 'Booking Proposal-New Client', 60, 'Level 2')");
        $this->addSql("INSERT INTO AbRequestStatus (BookerID, Status, `Order`, Level) VALUES (116000, 'Booking Proposal-Repeat Client', 70, 'Level 2')");

        $this->addSql("INSERT INTO AbRequestStatus (BookerID, Status, `Order`, Level) VALUES (116000, 'Reveal Flight/Acct. Details', 90, 'Level 3')");
        $this->addSql("INSERT INTO AbRequestStatus (BookerID, Status, `Order`, Level) VALUES (116000, 'Reveal Flight/Acct. Details (Amex/Chase)', 100, 'Level 3')");
        $this->addSql("INSERT INTO AbRequestStatus (BookerID, Status, `Order`, Level) VALUES (116000, 'Client Question/Objection', 110, 'Level 3')");
        $this->addSql("INSERT INTO AbRequestStatus (BookerID, Status, `Order`, Level) VALUES (116000, 'Client Concern/Objection Responded', 120, 'Level 3')");

        $this->addSql("INSERT INTO AbRequestStatus (BookerID, Status, `Order`, Level) VALUES (116000, 'Acct Share Request #1', 140, 'Level 4')");
        $this->addSql("INSERT INTO AbRequestStatus (BookerID, Status, `Order`, Level) VALUES (116000, 'Acct Share Request #2', 150, 'Level 4')");
        $this->addSql("INSERT INTO AbRequestStatus (BookerID, Status, `Order`, Level) VALUES (116000, 'Client Share Request #3-Cancel', 160, 'Level 4')");

        $this->addSql("INSERT INTO AbRequestStatus (BookerID, Status, `Order`, Level) VALUES (116000, 'Get Courtesy Hold', 180, 'Level 5')");
        $this->addSql("INSERT INTO AbRequestStatus (BookerID, Status, `Order`, Level) VALUES (116000, 'Courtesy Hold Booked', 190, 'Level 5')");
        $this->addSql("INSERT INTO AbRequestStatus (BookerID, Status, `Order`, Level) VALUES (116000, 'Acct. Shared/Booking Queue', 200, 'Level 5')");
        $this->addSql("INSERT INTO AbRequestStatus (BookerID, Status, `Order`, Level) VALUES (116000, 'No/Partial Award; Addtl. Search', 210, 'Level 5')");
        $this->addSql("INSERT INTO AbRequestStatus (BookerID, Status, `Order`, Level) VALUES (116000, '24 Hr Award Cancel Alert', 220, 'Level 5')");
        $this->addSql("INSERT INTO AbRequestStatus (BookerID, Status, `Order`, Level) VALUES (116000, '24 Hr Award Cancelled', 230, 'Level 5')");
    }

    public function down(Schema $schema): void
    {
        $this->addSql("ALTER TABLE AbRequestStatus DROP COLUMN `Order`");
        $this->addSql("ALTER TABLE AbRequestStatus DROP COLUMN `Level`");
        $this->addSql("DELETE FROM AbRequestStatus WHERE BookerID = 116000");
    }
}
