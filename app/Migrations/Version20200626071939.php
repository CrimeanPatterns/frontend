<?php

declare(strict_types=1);

namespace AwardWallet\MainBundle\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20200626071939 extends AbstractMigration
{
    public function up(Schema $schema): void
    {
        $this->addSql("
            ALTER TABLE AbRequest 
                DROP FinalServiceFee,
                DROP FinalTaxes,
                DROP FeesPaidTo,
                DROP INDEX IDX_D468CD51E8DC8944,
                DROP FOREIGN KEY FK_AbRBookingTransactionID,
                DROP BookingTransactionID;
        ");
    }

    public function down(Schema $schema): void
    {
        $this->addSql("
            ALTER TABLE AbRequest 
                ADD BookingTransactionID INT DEFAULT NULL COMMENT 'Ссылка на транзакцию. Транзакции создаются каждый месяц в результате взаиморасчетов между нами и букером' AFTER UserID,
                ADD INDEX IDX_D468CD51E8DC8944 (BookingTransactionID),
                ADD CONSTRAINT FK_AbRBookingTransactionID FOREIGN KEY (BookingTransactionID) REFERENCES AbTransaction (AbTransactionID) ON DELETE RESTRICT,
                ADD FinalServiceFee DECIMAL(10,2) DEFAULT NULL AFTER BookingTransactionID,
                ADD FinalTaxes DECIMAL(10,2) DEFAULT NULL AFTER FinalServiceFee,
                ADD FeesPaidTo INT UNSIGNED DEFAULT NULL AFTER FinalTaxes;
        ");
    }
}
