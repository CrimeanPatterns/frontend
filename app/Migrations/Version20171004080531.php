<?php

namespace AwardWallet\MainBundle\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
class Version20171004080531 extends AbstractMigration
{
    public function up(Schema $schema): void
    {
        $this->addSql('alter table AirlineAlias add column Trusted tinyint not null default 10');
        $this->addSql('create unique index AirlineAlias_uq_1 on AirlineAlias(Alias)');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('alter table AirlineAlias drop column Trusted');
        $this->addSql('drop index AirlineAlias_uq_1 on AirlineAlias');
    }
}
