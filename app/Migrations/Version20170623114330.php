<?php

namespace AwardWallet\MainBundle\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
class Version20170623114330 extends AbstractMigration
{
    public function up(Schema $schema): void
    {
        $this->addSql("alter table `RetailProvider`add column `InitialCode` varchar(200) not null comment 'первоначальный код' after `Code`");
        $this->addSql('update `RetailProvider` set `InitialCode` = `Code`');
        $this->addSql('
            update `RetailProvider` rp
            join `Provider` p on p.Code = rp.InitialCode
            set rp.DetectedProviderID = p.ProviderID
            where 
                rp.DetectedProviderID is null and
                rp.ImportedProviderID is null  
        ');
    }

    public function down(Schema $schema): void
    {
        $this->addSql("alter table `RetailProvider` drop column `InitialCode`");
    }
}
