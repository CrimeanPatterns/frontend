<?php

namespace AwardWallet\MainBundle\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
class Version20170401164908 extends AbstractMigration
{
    public function up(Schema $schema): void
    {
        $this->addSql("alter table `MobileDevice` add column `UpdateDate` datetime default null comment 'Дата и время вылета' after `CreationDate` ");
        $this->addSql('update `MobileDevice` set `UpdateDate` = `CreationDate` where `UpdateDate` is null');
        $this->addSql("alter table `MobileDevice` modify column `UpdateDate` datetime not null comment 'Дата и время вылета'");
    }

    public function down(Schema $schema): void
    {
        $this->addSql('alter table `MobileDevice` drop column `UpdateDate`');
    }
}
