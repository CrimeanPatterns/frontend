<?php

declare(strict_types=1);

namespace AwardWallet\MainBundle\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20240213084224 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        $this->addSql("create table EmailFromAddressCnt(
            EmailFromAddressCntID int not null auto_increment,
            EmailFromAddressID int not null,
            Cnt int not null,
            PeriodMonth date not null default '2024-02-01',
            primary key (EmailFromAddressCntID),
            foreign key (EmailFromAddressID) references EmailFromAddress(EmailFromAddressID),
            unique key (EmailFromAddressID, PeriodMonth)
        ) engine=InnoDB");
        $this->addSql("insert into EmailFromAddressCnt (EmailFromAddressID, Cnt) select EmailFromAddressID, Cnt from EmailFromAddress");
        $this->addSql("alter table EmailFromAddress drop column Cnt");
        $this->addSql("alter table EmailFromAddress add column LastReceivedDate date");
    }

    public function down(Schema $schema): void
    {
        $this->addSql("alter table EmailFromAddress drop column LastReceivedDate");
        $this->addSql("alter table EmailFromAddress add column Cnt int not null default 1");
        $this->addSql("drop table if exists EmailFromAddressCnt");
    }
}
