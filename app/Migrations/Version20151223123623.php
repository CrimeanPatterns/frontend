<?php

namespace AwardWallet\MainBundle\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * added KeepUpgraded field to UserAgent entity.
 */
class Version20151223123623 extends AbstractMigration
{
    public function up(Schema $schema): void
    {
        $this->addSql("alter table UserAgent add KeepUpgraded tinyint not null default 0 COMMENT 'Апгрейдить ли пользователя до AwPlus за счет средств связанного бизнеса' AFTER SendEmails");
    }

    public function down(Schema $schema): void
    {
        $this->addSql("ALTER TABLE `UserAgent` DROP `KeepUpgraded`");
    }
}
