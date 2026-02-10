<?php

namespace AwardWallet\MainBundle\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

class Version20140805054307 extends AbstractMigration
{
    public function up(Schema $schema): void
    {
        $this->addSql("alter table Usr add ReferralID varchar(40) comment 'ID рефералла, будет отправлено реферраллу по достижении определенных условий, например добавления аккаунта'");
    }

    public function down(Schema $schema): void
    {
        $this->addSql("alter table Usr drop ReferralID");
    }
}
