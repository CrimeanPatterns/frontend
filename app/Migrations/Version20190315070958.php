<?php

declare(strict_types=1);

namespace AwardWallet\MainBundle\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20190315070958 extends AbstractMigration
{
    public function up(Schema $schema): void
    {
        $this->addSql("alter table MileValue 
            add TravelersCount tinyint not null default 1, 
            add Status char(1) not null default 'N' comment 'see CalcMileValueCommand::STATUSES',
            add key (Status),
            modify Route varchar(120) not null, 
            modify MileRoute varchar(250) not null, 
            modify CashRoute varchar(250) not null 
        ");
    }

    public function down(Schema $schema): void
    {
        $this->addSql("alter table MileValue drop column TravelersCount, drop column Status");
    }
}
