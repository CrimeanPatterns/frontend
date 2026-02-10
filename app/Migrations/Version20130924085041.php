<?php

namespace AwardWallet\MainBundle\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;
use Symfony\Component\DependencyInjection;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
class Version20130924085041 extends AbstractMigration implements DependencyInjection\ContainerAwareInterface
{
    private $container;

    public function setContainer(DependencyInjection\ContainerInterface $container = null)
    {
        $this->container = $container;
    }

    public function up(Schema $schema): void
    {
        /** @var $doctrine \Doctrine\Bundle\DoctrineBundle\Registry */
        $doctrine = $this->container->get('doctrine');
        $conn = $doctrine->getConnection();

        // Remove AbAccountProgram.SubAccountID
        $table = $schema->getTable('AbAccountProgram');

        if ($table->hasColumn('SubAccountID') && $table->hasForeignKey('FK_AbAPSubaccount')) {
            $table->removeForeignKey('FK_AbAPSubaccount');
        }

        if ($table->hasColumn('SubAccountID') && $table->hasIndex('FK_AbAPSubaccount')) {
            $table->dropIndex('FK_AbAPSubaccount');
        }

        if ($table->hasIndex('RequestAccount')) {
            $table->dropIndex('RequestAccount');
        }
        $this->addSql("
			DELETE t1 FROM AbAccountProgram AS t1, AbAccountProgram AS t2
            WHERE t1.RequestID = t2.RequestID
                AND t1.AccountID = t2.AccountID
                AND t1.AbAccountProgramID > t2.AbAccountProgramID
		");
        $table->addUniqueIndex(['RequestID', 'AccountID'], 'RequestAccount');

        if ($table->hasColumn('SubAccountID')) {
            $table->dropColumn('SubAccountID');
        }

        // Remove AbRequest.Passengers
        $table = $schema->getTable('AbRequest');

        if ($table->hasColumn('Passengers')) {
            $table->dropColumn('Passengers');
        }

        // Remove AbRequest.FinalServiceFee, AbRequest.FinalTaxes, AbRequest.FeesPaidToUserID
        $value = function ($v, $default = null) {
            return (trim($v) === "") ? $default : $v;
        };
        $stmt = $conn->executeQuery("
            SELECT * FROM AbRequest
        ");

        while ($row = $stmt->fetch(\PDO::FETCH_ASSOC)) {
            if ($value($row['FinalServiceFee']) !== null
                || $value($row['FinalTaxes']) !== null
                || $value($row['FeesPaidToUserID']) !== null
            ) {
                $conn->insert('AbMessage', [
                    'CreateDate' => date("Y-m-d H:i:s"),
                    'Post' => null,
                    'Internal' => 0,
                    'Type' => -2,
                    'Metadata' => serialize(['Fee' => $value($row['FinalServiceFee']), 'Taxes' => $value($row['FinalTaxes']), 'To' => $value($row['FeesPaidToUserID'])]),
                    'RequestID' => $row['AbRequestID'],
                    'UserID' => 116000,
                ]);
            }
        }
        $table->dropColumn('FinalServiceFee');
        $table->dropColumn('FinalTaxes');

        if ($table->hasForeignKey('FK_AbRFeesPaidToUserID')) {
            $table->removeForeignKey('FK_AbRFeesPaidToUserID');
        }

        if ($table->hasIndex('IDX_D468CD512BD53A6A')) {
            $table->dropIndex('IDX_D468CD512BD53A6A');
        }
        $table->dropColumn('FeesPaidToUserID');

        // Fix foreign keys
        $table = $schema->getTable('AbAccountProgram');

        if ($table->hasForeignKey('FK_199DBEBB18FCD26A')) {
            $table->removeForeignKey('FK_199DBEBB18FCD26A');
        }
        $table->addForeignKeyConstraint($schema->getTable('AbRequest'), ['RequestID'], ['AbRequestID'], ['onDelete' => 'CASCADE'], 'FK_AbAPRequestID');

        if ($table->hasForeignKey('FK_199DBEBBDB411183')) {
            $table->removeForeignKey('FK_199DBEBBDB411183');
        }
        $table->addForeignKeyConstraint($schema->getTable('Account'), ['AccountID'], ['AccountID'], ['onDelete' => 'CASCADE'], 'FK_AbAPAccountID');

        $table = $schema->getTable('AbBookerInfo');

        if ($table->hasForeignKey('FK_AbBIUserID')) {
            $table->removeForeignKey('FK_AbBIUserID');
        }
        $table->addForeignKeyConstraint($schema->getTable('Usr'), ['UserID'], ['UserID'], ['onDelete' => 'CASCADE'], 'FK_AbBIUserID');

        $table = $schema->getTable('AbCustomProgram');

        if ($table->hasForeignKey('FK_AbCPRequestID')) {
            $table->removeForeignKey('FK_AbCPRequestID');
        }
        $table->addForeignKeyConstraint($schema->getTable('AbRequest'), ['RequestID'], ['AbRequestID'], ['onDelete' => 'CASCADE'], 'FK_AbCPRequestID');

        $table = $schema->getTable('AbInvoice');

        if ($table->hasForeignKey('FK_AbIMessageID')) {
            $table->removeForeignKey('FK_AbIMessageID');
        }
        $table->addForeignKeyConstraint($schema->getTable('AbMessage'), ['MessageID'], ['AbMessageID'], ['onDelete' => 'CASCADE'], 'FK_AbIMessageID');

        $table = $schema->getTable('AbInvoiceMiles');

        if ($table->hasForeignKey('FK_AbIMInvoiceID')) {
            $table->removeForeignKey('FK_AbIMInvoiceID');
        }
        $table->addForeignKeyConstraint($schema->getTable('AbInvoice'), ['InvoiceID'], ['AbInvoiceID'], ['onDelete' => 'CASCADE'], 'FK_AbIMInvoiceID');

        $table = $schema->getTable('AbMessage');

        if ($table->hasForeignKey('FK_AbMUserID')) {
            $table->removeForeignKey('FK_AbMUserID');
        }
        $this->addSql("ALTER TABLE `AbMessage` CHANGE `UserID` `UserID` INT(11)  NULL;");
        $table->addForeignKeyConstraint($schema->getTable('Usr'), ['UserID'], ['UserID'], ['onDelete' => 'SET NULL'], 'FK_AbMUserID');

        if ($table->hasForeignKey('FK_AbMRequestID')) {
            $table->removeForeignKey('FK_AbMRequestID');
        }
        $table->addForeignKeyConstraint($schema->getTable('AbRequest'), ['RequestID'], ['AbRequestID'], ['onDelete' => 'CASCADE'], 'FK_AbMRequestID');

        $table = $schema->getTable('AbPassenger');

        if ($table->hasForeignKey('FK_AbPRequestID')) {
            $table->removeForeignKey('FK_AbPRequestID');
        }
        $table->addForeignKeyConstraint($schema->getTable('AbRequest'), ['RequestID'], ['AbRequestID'], ['onDelete' => 'CASCADE'], 'FK_AbPRequestID');

        $table = $schema->getTable('AbRequest');

        if ($table->hasForeignKey('FK_AbRAssignedUserID')) {
            $table->removeForeignKey('FK_AbRAssignedUserID');
        }
        $table->addForeignKeyConstraint($schema->getTable('Usr'), ['AssignedUserID'], ['UserID'], ['onDelete' => 'SET NULL'], 'FK_AbRAssignedUserID');

        if ($table->hasForeignKey('FK_AbRBookerUserID')) {
            $table->removeForeignKey('FK_AbRBookerUserID');
        }
        $table->addForeignKeyConstraint($schema->getTable('Usr'), ['BookerUserID'], ['UserID'], ['onDelete' => 'CASCADE'], 'FK_AbRBookerUserID');

        if ($table->hasForeignKey('FK_AbRBookingTransactionID')) {
            $table->removeForeignKey('FK_AbRBookingTransactionID');
        }
        $table->addForeignKeyConstraint($schema->getTable('AbTransaction'), ['BookingTransactionID'], ['AbTransactionID'], ['onDelete' => 'SET NULL'], 'FK_AbRBookingTransactionID');

        if ($table->hasForeignKey('FK_AbRUserID')) {
            $table->removeForeignKey('FK_AbRUserID');
        }
        $this->addSql("ALTER TABLE `AbRequest` CHANGE `UserID` `UserID` INT(11)  NULL;");
        $table->addForeignKeyConstraint($schema->getTable('Usr'), ['UserID'], ['UserID'], ['onDelete' => 'SET NULL'], 'FK_AbRUserID');

        $table = $schema->getTable('AbRequestRead');

        if ($table->hasForeignKey('FK_AbRRRequestID')) {
            $table->removeForeignKey('FK_AbRRRequestID');
        }
        $table->addForeignKeyConstraint($schema->getTable('AbRequest'), ['RequestID'], ['AbRequestID'], ['onDelete' => 'CASCADE'], 'FK_AbRRRequestID');

        if ($table->hasForeignKey('FK_AbRRUserID')) {
            $table->removeForeignKey('FK_AbRRUserID');
        }
        $table->addForeignKeyConstraint($schema->getTable('Usr'), ['UserID'], ['UserID'], ['onDelete' => 'CASCADE'], 'FK_AbRRUserID');

        $table = $schema->getTable('AbSegment');

        if ($table->hasForeignKey('FK_AbSRequestID')) {
            $table->removeForeignKey('FK_AbSRequestID');
        }
        $table->addForeignKeyConstraint($schema->getTable('AbRequest'), ['RequestID'], ['AbRequestID'], ['onDelete' => 'CASCADE'], 'FK_AbSRequestID');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
    }
}
