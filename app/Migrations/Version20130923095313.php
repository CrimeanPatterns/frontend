<?php

namespace AwardWallet\MainBundle\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
class Version20130923095313 extends AbstractMigration
{
    public function up(Schema $schema): void
    {
        $this->addSql("
            ALTER TABLE AbInvoice CHANGE MessageID MessageID BIGINT(15)  NOT NULL;


            ALTER TABLE AbAccountProgram ADD CONSTRAINT FK_AbAPSubaccount FOREIGN KEY (SubAccountID) REFERENCES SubAccount (SubAccountID) ON DELETE RESTRICT;
			ALTER TABLE AbCustomProgram ADD CONSTRAINT FK_AbCPRequestID FOREIGN KEY (RequestID) REFERENCES AbRequest (AbRequestID) ON DELETE RESTRICT;
			ALTER TABLE AbInvoice ADD CONSTRAINT FK_AbIMessageID FOREIGN KEY (MessageID) REFERENCES AbMessage (AbMessageID) ON DELETE RESTRICT;
			ALTER TABLE AbInvoiceMiles ADD CONSTRAINT FK_AbIMInvoiceID FOREIGN KEY (InvoiceID) REFERENCES AbInvoice (AbInvoiceID);
			ALTER TABLE AbMessage ADD CONSTRAINT FK_AbMRequestID FOREIGN KEY (RequestID) REFERENCES AbRequest (AbRequestID) ON DELETE RESTRICT;
			ALTER TABLE AbMessage ADD CONSTRAINT FK_AbMUserID FOREIGN KEY (UserID) REFERENCES Usr (UserID) ON DELETE RESTRICT;
			ALTER TABLE AbPassenger ADD CONSTRAINT FK_AbPRequestID FOREIGN KEY (RequestID) REFERENCES AbRequest (AbRequestID) ON DELETE RESTRICT;
			ALTER TABLE AbRequest ADD CONSTRAINT FK_AbRBookerUserID FOREIGN KEY (BookerUserID) REFERENCES Usr (UserID) ON DELETE RESTRICT;
			ALTER TABLE AbRequest ADD CONSTRAINT FK_AbRAssignedUserID FOREIGN KEY (AssignedUserID) REFERENCES Usr (UserID) ON DELETE RESTRICT;
			ALTER TABLE AbRequest ADD CONSTRAINT FK_AbRUserID FOREIGN KEY (UserID) REFERENCES Usr (UserID) ON DELETE RESTRICT;
			ALTER TABLE AbRequest ADD CONSTRAINT FK_AbRBookingTransactionID FOREIGN KEY (BookingTransactionID) REFERENCES AbTransaction (AbTransactionID) ON DELETE RESTRICT;
			ALTER TABLE AbRequest ADD CONSTRAINT FK_AbRFeesPaidToUserID FOREIGN KEY (FeesPaidToUserID) REFERENCES Usr (UserID) ON DELETE RESTRICT;
			ALTER TABLE AbRequestRead ADD CONSTRAINT FK_AbRRUserID FOREIGN KEY (UserID) REFERENCES Usr (UserID) ON DELETE RESTRICT;
			ALTER TABLE AbRequestRead ADD CONSTRAINT FK_AbRRRequestID FOREIGN KEY (RequestID) REFERENCES AbRequest (AbRequestID) ON DELETE RESTRICT;
			ALTER TABLE AbSegment ADD CONSTRAINT FK_AbSRequestID FOREIGN KEY (RequestID) REFERENCES AbRequest (AbRequestID) ON DELETE RESTRICT;
			ALTER TABLE AbBookerInfo ADD CONSTRAINT FK_AbBIUserID FOREIGN KEY (UserID) REFERENCES Usr (UserID) ON DELETE RESTRICT;
        ");
    }

    public function down(Schema $schema): void
    {
        $this->addSql("
            ALTER TABLE `AbAccountProgram` DROP FOREIGN KEY `FK_AbAPSubaccount`;
            ALTER TABLE `AbCustomProgram` DROP FOREIGN KEY `FK_AbCPRequestID`;
            ALTER TABLE `AbInvoice` DROP FOREIGN KEY `FK_AbIMessageID`;
            ALTER TABLE `AbInvoiceMiles` DROP FOREIGN KEY `FK_AbIMInvoiceID`;
            ALTER TABLE `AbMessage` DROP FOREIGN KEY `FK_AbMRequestID`;
            ALTER TABLE `AbMessage` DROP FOREIGN KEY `FK_AbMUserID`;
            ALTER TABLE `AbPassenger` DROP FOREIGN KEY `FK_AbPRequestID`;
            ALTER TABLE `AbRequest` DROP FOREIGN KEY `FK_AbRBookerUserID`;
            ALTER TABLE `AbRequest` DROP FOREIGN KEY `FK_AbRAssignedUserID`;
            ALTER TABLE `AbRequest` DROP FOREIGN KEY `FK_AbRUserID`;
            ALTER TABLE `AbRequest` DROP FOREIGN KEY `FK_AbRBookingTransactionID`;
            ALTER TABLE `AbRequest` DROP FOREIGN KEY `FK_AbRFeesPaidToUserID`;
            ALTER TABLE `AbRequestRead` DROP FOREIGN KEY `FK_AbRRUserID`;
            ALTER TABLE `AbRequestRead` DROP FOREIGN KEY `FK_AbRRRequestID`;
            ALTER TABLE `AbSegment` DROP FOREIGN KEY `FK_AbSRequestID`;
            ALTER TABLE `AbBookerInfo` DROP FOREIGN KEY `FK_AbBIUserID`;

            ALTER TABLE AbInvoice CHANGE MessageID MessageID INT(11)  NOT NULL;
        ");
    }
}
