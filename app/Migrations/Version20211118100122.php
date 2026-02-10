<?php

declare(strict_types=1);

namespace AwardWallet\MainBundle\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20211118100122 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        $this->addSql("alter table CreditCardBonusLimit
            add RegisterBy date,
            add MustRegister tinyint not null default 0,
            add Targeted tinyint not null default 0,
            add CCOpenedBy date,
            add NewAccountsOnly tinyint not null default 0
        ");
    }

    public function down(Schema $schema): void
    {
        $this->addSql("alter table CreditCardBonusLimit
            drop RegisterBy,
            drop MustRegister,
            drop Targeted,
            drop CCOpenedByBy,
            drop NewAccountsOnly
        ");
    }
}
