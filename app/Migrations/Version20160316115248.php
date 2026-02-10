<?php

namespace AwardWallet\MainBundle\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

class Version20160316115248 extends AbstractMigration
{
    public function up(Schema $schema): void
    {
        $this->addSql("alter table Account
            add ErrorDate datetime comment 'Когда аккаунт последний раз сменил ErrorCode' after ErrorCount,
            modify InvalidPassCount int not null default 0 comment 'Удалить после завершения #12447, вместо этого поля теперь ErrorCount'");
    }

    public function down(Schema $schema): void
    {
        $this->addSql("alter table Account
            drop ErrorDate,
            modify InvalidPassCount int not null default 0 comment 'Количетсво попыток логина с неверными кренделями'");
    }
}
