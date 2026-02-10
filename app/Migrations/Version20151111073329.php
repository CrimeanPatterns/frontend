<?php

namespace AwardWallet\MainBundle\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

class Version20151111073329 extends AbstractMigration
{
    public function up(Schema $schema): void
    {
        $this->addSql("alter table AccountHistory add Position int comment 'Позиция строки, если даты одинаковые. Строки с меньшими числами - более новые.'");
    }

    public function down(Schema $schema): void
    {
        $this->addSql("alter table AccountHistory drop Position");
    }
}
