<?php

namespace AwardWallet\MainBundle\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

class Version20160322131307 extends AbstractMigration
{
    public function up(Schema $schema): void
    {
        $this->addSql("alter table Account
            add DisableDate datetime comment 'Дата отключения' after Disabled,
            add DisableReason tinyint comment 'Причина отключения, Account::DISABLE_REASON_XX' after DisableDate");

        $this->addSql("update Account set Disabled = 1 where ErrorCode in (" . ACCOUNT_PREVENT_LOCKOUT . ", " . ACCOUNT_LOCKOUT . ")");
    }

    public function down(Schema $schema): void
    {
        $this->addSql("alter table Account drop DisableDate, drop DisableReason");
    }
}
