<?php

namespace AwardWallet\MainBundle\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
class Version20170913122312 extends AbstractMigration
{
    public function up(Schema $schema): void
    {
        $this->addSql("
            alter table `CardImage` 
                add column `ProviderID` int(11) default null comment 'провайдер, к которому привязан аккаунт',
                add constraint `fk_CardImage_Account_ProviderID` foreign key (ProviderID) references Provider(ProviderID) on delete set null,
                add constraint `fk_CardImage_Account_DetectedProviderID` foreign key (DetectedProviderID) references Provider(ProviderID) on delete set null
        ");

        $this->addSql('
            update `CardImage` ci
            join `Account` a on 
                ci.AccountID = a.AccountID
            set
                ci.ProviderID = a.ProviderID
        ');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('
            alter table `CardImage`
                drop foreign key `fk_CardImage_Account_DetectedProviderID`,
                drop foreign key `fk_CardImage_Account_ProviderID`,
                drop column `ProviderID`
        ');
    }
}
