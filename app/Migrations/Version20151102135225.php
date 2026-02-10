<?php

namespace AwardWallet\MainBundle\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

class Version20151102135225 extends AbstractMigration
{
    public function up(Schema $schema): void
    {
        $this->addSql("alter table Account add HistoryVersion int comment 'Используется для частичного парсинга истории с WSDL' after LastCheckHistoryDate");
    }

    public function down(Schema $schema): void
    {
        $this->addSql("alter table Account drop HistoryVersion");
    }
}
