<?php

declare(strict_types=1);

namespace AwardWallet\MainBundle\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20220629063751 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        return;
        $this->addSql("alter table Merchant modify TransactionsLast3Months bigint default 0 not null comment 'Транзакции за последние 3 месяца'");
    }

    public function down(Schema $schema): void
    {
        return;
        $this->addSql("alter table Merchant modify TransactionsLast3Months bigint default null comment 'Транзакции за последние 3 месяца'");
    }
}
