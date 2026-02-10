<?php

declare(strict_types=1);

namespace AwardWallet\MainBundle\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20220629060922 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        $this->addSql("alter table Merchant 
            add TransactionsLast3Months bigint comment 'Транзакции за последние 3 месяца' after Transactions,
            add index idxTransactionsLast3Months(TransactionsLast3Months)
        ");

    }

    public function down(Schema $schema): void
    {
        $this->addSql('
            alter table Merchant
                drop index idxTransactionsLast3Months,
                drop TransactionsLast3Months
        ');

    }
}
