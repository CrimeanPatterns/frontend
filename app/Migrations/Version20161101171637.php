<?php

namespace AwardWallet\MainBundle\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
class Version20161101171637 extends AbstractMigration
{
    public function up(Schema $schema): void
    {
        $this->addSql("alter table ProviderCoupon add constraint fkUsr foreign key (UserID) references Usr(UserID) on delete cascade");
        $this->addSql("alter table ProviderCoupon drop foreign key fkProviderCoupon_ref_Usr");
    }

    public function down(Schema $schema): void
    {
        $this->addSql("alter table ProviderCoupon add constraint fkProviderCoupon_ref_Usr foreign key fkProviderCoupon_ref_Usr(UserID) references Usr(UserID) on delete cascade");
        $this->addSql("alter table ProviderCoupon drop foreign key fkUsr, drop key fkUsr");
    }
}
