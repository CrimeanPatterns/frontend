<?php

namespace AwardWallet\MainBundle\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

class Version20141028132327 extends AbstractMigration
{
    public function up(Schema $schema): void
    {
        $this->addSql("alter table Provider add CanReceiveEmail tinyint not null default 0 comment 'Можем ли мы доставать баланс или стэйтменты из писем этого провайдера'");
        $this->addSql("update Provider set CanReceiveEmail = 1 where Code in ('delta', 'mileageplus', 'deltacorp', 'rapidrewards')");
    }

    public function down(Schema $schema): void
    {
        $this->addSql("alter table Provider drop column CanReceiveEmail");
    }
}
