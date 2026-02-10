<?php

declare(strict_types=1);

namespace AwardWallet\MainBundle\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20220721091007 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        $this->addSql("alter table `Merchant` 
            add TransactionsConfidenceInterval int not null default 0 comment 'количество транзакций в доверительном интервале' after `TransactionsLast3Months`, 
            add ConfidenceIntervalStartDate datetime default null comment 'начало доверительного интервала' after `TransactionsConfidenceInterval`
        ");
    }

    public function down(Schema $schema): void
    {
        $this->addSql('alter table Merchant
            drop ConfidenceIntervalStartDate,
            drop TransactionsConfidenceInterval
        ');
    }
}
