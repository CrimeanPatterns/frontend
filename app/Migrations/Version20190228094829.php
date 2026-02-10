<?php

declare(strict_types=1);

namespace AwardWallet\MainBundle\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20190228094829 extends AbstractMigration
{
    public function up(Schema $schema): void
    {
        $this->addSql("alter table Provider add column Login2AsCountry int(1) default 0 not null comment 'показывать ли регионы(ProviderCountry) в поле Login2' after Login2Required");
        $this->addSql("
            update Provider p
            join (
               select ProviderID, count(*) as Cnt
               from ProviderCountry pc
               group by pc.ProviderID
               having Cnt > 1
            ) countryStat
            set Login2AsCountry = 1
            where 
               p.ProviderID = countryStat.ProviderID and
               p.Login2Caption <> ''
        ");
    }

    public function down(Schema $schema): void
    {
        $this->addSql('alter table Provider drop column `Login2AsCountry`');
    }
}
