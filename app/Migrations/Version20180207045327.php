<?php

namespace AwardWallet\MainBundle\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
class Version20180207045327 extends AbstractMigration
{
    public function up(Schema $schema): void
    {
        $table = $schema->getTable('Cart');

        if (!$table->hasIndex('PaymentType_BillingTransactionID')) {
            $table->addUniqueIndex(['PaymentType', 'BillingTransactionID'], 'PaymentType_BillingTransactionID');
        }
    }

    public function down(Schema $schema): void
    {
        $schema->getTable('Cart')->dropIndex('PaymentType_BillingTransactionID');
    }
}
