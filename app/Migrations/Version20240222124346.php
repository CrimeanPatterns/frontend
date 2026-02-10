<?php

declare(strict_types=1);

namespace AwardWallet\MainBundle\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20240222124346 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        $this->addSql("alter table EmailFromAddressCnt
                add column CntStatement int not null default 0, 
                add column CntDiscovered int not null default 0,
                add column CntItinerary int not null default 0,
                add column CntOtc int not null default 0,
                add column CntBp int not null default 0,
                add column CntOther int not null default 0");
        $this->addSql("alter table EmailFromAddress add column LastUpdateDate date not null default '2024-02-22'");
    }

    public function down(Schema $schema): void
    {
        $this->addSql("alter table EmailFromAddressCnt drop column CntStatement,
                        drop column CntDiscovered,
                        drop column CntItinerary,
                        drop column CntOtc,
                        drop column CntBp,
                        drop column CntOther");
        $this->addSql("alter table EmailFromAddress drop column LastUpdateDate");
    }
}
