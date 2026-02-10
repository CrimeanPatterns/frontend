<?php

namespace AwardWallet\MainBundle\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
class Version20140710033509 extends AbstractMigration
{
    public function up(Schema $schema): void
    {
        if ($schema->hasTable('UserBookerBAK')) {
            $schema->dropTable('UserBookerBAK');
        }

        if ($schema->hasTable('BookingTransactionBAK')) {
            $schema->dropTable('BookingTransactionBAK');
        }

        if ($schema->hasTable('BookingRequestBAK')) {
            $schema->dropTable('BookingRequestBAK');
        }

        if ($schema->hasTable('BookingHistoryBAK')) {
            $schema->dropTable('BookingHistoryBAK');
        }

        if ($schema->hasTable('BookingMessageBAK')) {
            $schema->dropTable('BookingMessageBAK');
        }

        if ($schema->hasTable('BookingInvoiceBAK')) {
            $schema->dropTable('BookingInvoiceBAK');
        }

        if ($schema->hasTable('BookingInvoiceMilesBAK')) {
            $schema->dropTable('BookingInvoiceMilesBAK');
        }

        if ($schema->hasTable('BookingRequestAccountBAK')) {
            $schema->dropTable('BookingRequestAccountBAK');
        }

        if ($schema->hasTable('BookingRequestCustomProgramBAK')) {
            $schema->dropTable('BookingRequestCustomProgramBAK');
        }

        if ($schema->hasTable('BookingMessageMarkBAK')) {
            $schema->dropTable('BookingMessageMarkBAK');
        }

        if ($schema->hasTable('BookingRequestMarkBAK')) {
            $schema->dropTable('BookingRequestMarkBAK');
        }

        if ($schema->hasTable('BookingRequestPassengerBAK')) {
            $schema->dropTable('BookingRequestPassengerBAK');
        }

        if ($schema->hasTable('BookingRequestSegmentBAK')) {
            $schema->dropTable('BookingRequestSegmentBAK');
        }

        if ($schema->hasTable('BookingInvoiceAccessLogBAK')) {
            $schema->dropTable('BookingInvoiceAccessLogBAK');
        }

        if ($schema->hasTable('BookingSharingRequestBAK')) {
            $schema->dropTable('BookingSharingRequestBAK');
        }

        if ($schema->hasTable('BookingRequestProviderBAK')) {
            $schema->dropTable('BookingRequestProviderBAK');
        }
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
    }
}
