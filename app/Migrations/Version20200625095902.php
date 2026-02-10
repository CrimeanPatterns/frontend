<?php

declare(strict_types=1);

namespace AwardWallet\MainBundle\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20200625095902 extends AbstractMigration
{
    public function up(Schema $schema): void
    {
        $this->addSql("
            ALTER TABLE AbInvoice 
                ADD PaidTo INT UNSIGNED DEFAULT NULL COMMENT 'Кому заплатили деньги, UserID' AFTER PaymentType,
                ADD TransactionID INT DEFAULT NULL COMMENT 'Ссылка на транзакцию. Транзакции создаются каждый месяц в результате взаиморасчетов между нами и букером' AFTER PaidTo,
                ADD CONSTRAINT FK_TransactionID FOREIGN KEY (TransactionID) REFERENCES AbTransaction (AbTransactionID) ON DELETE RESTRICT;
        ");
        $this->addSql("ALTER TABLE AbInvoiceItem ADD Type TINYINT UNSIGNED NOT NULL DEFAULT '0' COMMENT 'Fee, Taxes или другое. Необходимо для корректного взаиморасчета с букером.' AFTER Description;");
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE AbInvoiceItem DROP Type;');
        $this->addSql('ALTER TABLE AbInvoice 
            DROP PaidTo, 
            DROP TransactionID,
            DROP FOREIGN KEY FK_TransactionID;
        ');
    }
}
