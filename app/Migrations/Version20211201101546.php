<?php

declare(strict_types=1);

namespace AwardWallet\MainBundle\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20211201101546 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        $this->addSql("alter table CreditCardBonusLimit 
            add PeriodBeginsOn char(1) not null default 'A'  comment 'see Schema for constants' after Period,
            add SignupBonus int after BonusMultiplier,
            add SignupBonusCurrency char(1) comment 'see Schema for constants' after SignupBonus,
            modify BonusMultiplier decimal(3,1) comment 'Процент кэшбэка'");
    }

    public function down(Schema $schema): void
    {
        $this->addSql("alter table CreditCardBonusLimit
            drop PeriodBeginsOn,
            drop SignupBonus,
            drop SignupBonusCurrency");
    }
}
