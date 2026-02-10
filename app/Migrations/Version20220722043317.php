<?php

declare(strict_types=1);

namespace AwardWallet\MainBundle\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20220722043317 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        $this->addSql("alter table `MerchantPattern` 
            add `Stat` json default null comment 'Статистика всем мерчантам этого паттерна, по картам и Multiplier, заполняется в AnalyzeMerchantStatsCommand',
            add TransactionsConfidenceInterval int not null default 0 comment 'количество транзакций в доверительном интервале', 
            add ConfidenceIntervalStartDate datetime default null comment 'начало доверительного интервала',
            algorithm=instant 
        ");
    }

    public function down(Schema $schema): void
    {
        $this->addSql('alter table `MerchantPattern` 
            drop `Stat`,
            drop TransactionsConfidenceInterval,
            drop ConfidenceIntervalStartDate
        ');
    }
}
