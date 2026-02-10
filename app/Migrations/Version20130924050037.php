<?php

namespace AwardWallet\MainBundle\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;
use Symfony\Component\DependencyInjection;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
class Version20130924050037 extends AbstractMigration implements DependencyInjection\ContainerAwareInterface
{
    private $container;

    public function setContainer(DependencyInjection\ContainerInterface $container = null)
    {
        $this->container = $container;
    }

    public function up(Schema $schema): void
    {
        // Booking migration fix.
        /** @var $doctrine \Doctrine\Bundle\DoctrineBundle\Registry */
        $doctrine = $this->container->get('doctrine');
        $conn = $doctrine->getConnection();

        if (!$schema->getTable('AbMessage')->hasForeignKey('FK_AbMUserID')) {
            $stmt = $conn->executeQuery("
                SELECT
                  m.AbMessageID
                FROM
                  AbMessage m
                  LEFT OUTER JOIN Usr u ON u.UserID = m.UserID
                WHERE
                  m.UserID IS NOT NULL
                  AND u.UserID IS NULL
            ");
            $ids = [];

            while ($row = $stmt->fetch(\PDO::FETCH_ASSOC)) {
                $ids[] = $row['AbMessageID'];
            }

            if (sizeof($ids)) {
                $conn->executeUpdate('UPDATE AbMessage SET UserID = ? WHERE AbMessageID IN (?)', [116000, $ids], [\PDO::PARAM_INT, \Doctrine\DBAL\Connection::PARAM_INT_ARRAY]);
            }
        }

        if (!$schema->getTable('AbRequest')->hasForeignKey('FK_AbRAssignedUserID')) {
            $stmt = $conn->executeQuery("
                SELECT
                  r.AbRequestID
                FROM
                  AbRequest r
                  LEFT OUTER JOIN Usr u ON u.UserID = r.AssignedUserID
                WHERE
                  r.AssignedUserID IS NOT NULL
                  AND u.UserID IS NULL
            ");
            $ids = [];

            while ($row = $stmt->fetch(\PDO::FETCH_ASSOC)) {
                $ids[] = $row['AbRequestID'];
            }

            if (sizeof($ids)) {
                $conn->executeUpdate('UPDATE AbRequest SET AssignedUserID = NULL WHERE AbRequestID IN (?)', [$ids], [\Doctrine\DBAL\Connection::PARAM_INT_ARRAY]);
            }
        }

        if (!$schema->getTable('AbRequest')->hasForeignKey('FK_AbRBookingTransactionID')) {
            $stmt = $conn->executeQuery("
                SELECT
                  r.AbRequestID
                FROM
                  AbRequest r
                  LEFT OUTER JOIN AbTransaction t ON t.AbTransactionID = r.BookingTransactionID
                WHERE
                  r.BookingTransactionID IS NOT NULL
                  AND t.AbTransactionID IS NULL
            ");
            $ids = [];

            while ($row = $stmt->fetch(\PDO::FETCH_ASSOC)) {
                $ids[] = $row['AbRequestID'];
            }

            if (sizeof($ids)) {
                $conn->executeUpdate('UPDATE AbRequest SET BookingTransactionID = NULL WHERE AbRequestID IN (?)', [$ids], [\Doctrine\DBAL\Connection::PARAM_INT_ARRAY]);
            }
        }

        $table = $schema->getTable('AbAccountProgram');

        if ($table->hasColumn('SubAccountID') && !$table->hasForeignKey('FK_AbAPSubaccount')) {
            $table->addForeignKeyConstraint($schema->getTable('SubAccount'), ['SubAccountID'], ['SubAccountID'], [], 'FK_AbAPSubaccount');
        }
        $table = $schema->getTable('AbCustomProgram');

        if (!$table->hasForeignKey('FK_AbCPRequestID')) {
            $table->addForeignKeyConstraint($schema->getTable('AbRequest'), ['RequestID'], ['AbRequestID'], [], 'FK_AbCPRequestID');
        }
        $table = $schema->getTable('AbInvoice');

        if (!$table->hasForeignKey('FK_AbIMessageID')) {
            $table->addForeignKeyConstraint($schema->getTable('AbMessage'), ['MessageID'], ['AbMessageID'], [], 'FK_AbIMessageID');
        }
        $table = $schema->getTable('AbInvoiceMiles');

        if (!$table->hasForeignKey('FK_AbIMInvoiceID')) {
            $table->addForeignKeyConstraint($schema->getTable('AbInvoice'), ['InvoiceID'], ['AbInvoiceID'], [], 'FK_AbIMInvoiceID');
        }

        $table = $schema->getTable('AbMessage');

        if (!$table->hasForeignKey('FK_AbMRequestID')) {
            $table->addForeignKeyConstraint($schema->getTable('AbRequest'), ['RequestID'], ['AbRequestID'], [], 'FK_AbMRequestID');
        }

        if (!$table->hasForeignKey('FK_AbMUserID')) {
            $table->addForeignKeyConstraint($schema->getTable('Usr'), ['UserID'], ['UserID'], [], 'FK_AbMUserID');
        }

        $table = $schema->getTable('AbPassenger');

        if (!$table->hasForeignKey('FK_AbPRequestID')) {
            $table->addForeignKeyConstraint($schema->getTable('AbRequest'), ['RequestID'], ['AbRequestID'], [], 'FK_AbPRequestID');
        }

        $table = $schema->getTable('AbRequest');

        if (!$table->hasForeignKey('FK_AbRBookerUserID')) {
            $table->addForeignKeyConstraint($schema->getTable('Usr'), ['BookerUserID'], ['UserID'], [], 'FK_AbRBookerUserID');
        }

        if (!$table->hasForeignKey('FK_AbRAssignedUserID')) {
            $table->addForeignKeyConstraint($schema->getTable('Usr'), ['AssignedUserID'], ['UserID'], [], 'FK_AbRAssignedUserID');
        }

        if (!$table->hasForeignKey('FK_AbRUserID')) {
            $table->addForeignKeyConstraint($schema->getTable('Usr'), ['UserID'], ['UserID'], [], 'FK_AbRUserID');
        }

        if (!$table->hasForeignKey('FK_AbRBookingTransactionID')) {
            $table->addForeignKeyConstraint($schema->getTable('AbTransaction'), ['BookingTransactionID'], ['AbTransactionID'], [], 'FK_AbRBookingTransactionID');
        }

        if ($table->hasColumn('FeesPaidToUserID') && !$table->hasForeignKey('FK_AbRFeesPaidToUserID')) {
            $table->addForeignKeyConstraint($schema->getTable('Usr'), ['FeesPaidToUserID'], ['UserID'], [], 'FK_AbRFeesPaidToUserID');
        }

        $table = $schema->getTable('AbRequestRead');

        if (!$table->hasForeignKey('FK_AbRRUserID')) {
            $table->addForeignKeyConstraint($schema->getTable('Usr'), ['UserID'], ['UserID'], [], 'FK_AbRRUserID');
        }

        if (!$table->hasForeignKey('FK_AbRRRequestID')) {
            $table->addForeignKeyConstraint($schema->getTable('AbRequest'), ['RequestID'], ['AbRequestID'], [], 'FK_AbRRRequestID');
        }

        $table = $schema->getTable('AbSegment');

        if (!$table->hasForeignKey('FK_AbSRequestID')) {
            $table->addForeignKeyConstraint($schema->getTable('AbRequest'), ['RequestID'], ['AbRequestID'], [], 'FK_AbSRequestID');
        }

        $table = $schema->getTable('AbBookerInfo');

        if (!$table->hasForeignKey('FK_AbBIUserID')) {
            $table->addForeignKeyConstraint($schema->getTable('Usr'), ['UserID'], ['UserID'], [], 'FK_AbBIUserID');
        }
    }

    public function down(Schema $schema): void
    {
        $table = $schema->getTable('AbAccountProgram');

        if ($table->hasForeignKey('FK_AbAPSubaccount')) {
            $table->removeForeignKey('FK_AbAPSubaccount');
        }
        $table = $schema->getTable('AbCustomProgram');

        if ($table->hasForeignKey('FK_AbCPRequestID')) {
            $table->removeForeignKey('FK_AbCPRequestID');
        }
        $table = $schema->getTable('AbInvoice');

        if ($table->hasForeignKey('FK_AbIMessageID')) {
            $table->removeForeignKey('FK_AbIMessageID');
        }
        $table = $schema->getTable('AbInvoiceMiles');

        if ($table->hasForeignKey('FK_AbIMInvoiceID')) {
            $table->removeForeignKey('FK_AbIMInvoiceID');
        }

        $table = $schema->getTable('AbMessage');

        if ($table->hasForeignKey('FK_AbMRequestID')) {
            $table->removeForeignKey('FK_AbMRequestID');
        }

        if ($table->hasForeignKey('FK_AbMUserID')) {
            $table->removeForeignKey('FK_AbMUserID');
        }

        $table = $schema->getTable('AbPassenger');

        if ($table->hasForeignKey('FK_AbPRequestID')) {
            $table->removeForeignKey('FK_AbPRequestID');
        }

        $table = $schema->getTable('AbRequest');

        if ($table->hasForeignKey('FK_AbRBookerUserID')) {
            $table->removeForeignKey('FK_AbRBookerUserID');
        }

        if ($table->hasForeignKey('FK_AbRAssignedUserID')) {
            $table->removeForeignKey('FK_AbRAssignedUserID');
        }

        if ($table->hasForeignKey('FK_AbRUserID')) {
            $table->removeForeignKey('FK_AbRUserID');
        }

        if ($table->hasForeignKey('FK_AbRBookingTransactionID')) {
            $table->removeForeignKey('FK_AbRBookingTransactionID');
        }

        if ($table->hasForeignKey('FK_AbRFeesPaidToUserID')) {
            $table->removeForeignKey('FK_AbRFeesPaidToUserID');
        }

        $table = $schema->getTable('AbRequestRead');

        if ($table->hasForeignKey('FK_AbRRUserID')) {
            $table->removeForeignKey('FK_AbRRUserID');
        }

        if ($table->hasForeignKey('FK_AbRRRequestID')) {
            $table->removeForeignKey('FK_AbRRRequestID');
        }

        $table = $schema->getTable('AbSegment');

        if ($table->hasForeignKey('FK_AbSRequestID')) {
            $table->removeForeignKey('FK_AbSRequestID');
        }

        $table = $schema->getTable('AbBookerInfo');

        if ($table->hasForeignKey('FK_AbBIUserID')) {
            $table->removeForeignKey('FK_AbBIUserID');
        }
    }
}
