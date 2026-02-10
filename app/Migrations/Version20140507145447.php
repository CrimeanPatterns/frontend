<?php

namespace AwardWallet\MainBundle\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

class Version20140507145447 extends AbstractMigration
{
    public function up(Schema $schema): void
    {
        $this->addSql("alter table AbBookerInfo add FromEmail varchar(80) comment 'Будет установлено в поле From при отправке писем'");
        $this->addSql("update AbBookerInfo set FromEmail = 'steve@bookyouraward.com' where UserID = 116000");
    }

    public function down(Schema $schema): void
    {
        $this->addSql("alter table AbBookerInfo drop FromEmail");
    }
}
