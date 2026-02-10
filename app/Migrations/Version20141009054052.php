<?php

namespace AwardWallet\MainBundle\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

class Version20141009054052 extends AbstractMigration
{
    public function up(Schema $schema): void
    {
        $this->addSql("alter table Provider add CanSavePassword tinyint comment 'Где можно хранить пароль для этого провайдера, одна из констант SAVE_PASSWORD_DATABASE, SAVE_PASSWORD_LOCALLY'");
        $this->addSql("update Provider set CanSavePassword  = " . SAVE_PASSWORD_LOCALLY . " where Code = 'aa'");
    }

    public function down(Schema $schema): void
    {
        $this->addSql("alter table Provider drop CanSavePassword");
    }
}
