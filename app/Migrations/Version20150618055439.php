<?php

namespace AwardWallet\MainBundle\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

class Version20150618055439 extends AbstractMigration
{
    public function up(Schema $schema): void
    {
        $this->addSql("alter table Offer add LastUserID int comment 'Запоминается, до какого пользователя мы рассчитали этот оффер, оптимизация при расчете по крону'");
    }

    public function down(Schema $schema): void
    {
        $this->addSql("alter table Offer drop LastUserID");
    }
}
